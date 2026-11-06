<?php

namespace App\Actions\Fortify;

use App\Actions\Music\SendGuardianConsentRequest;
use App\Concerns\AccountValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Models\Consent;
use App\Models\User;
use App\Notifications\NewTeacherSignUp;
use App\Support\Verticals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Self sign-up for parents (AUTH-1), academic students (ACA-3), music
 * learners (MUS-3) and teachers and experts (HIR-8, approved by an admin
 * before they get any work). School students never sign up themselves; their parent
 * adds them. An under-18 music learner's parent approves by email.
 * Someone joining with Google (AUTH-7) answers the same questions, without
 * a password, and their email is already verified by Google.
 */
class CreateNewUser implements CreatesNewUsers
{
    use AccountValidationRules, PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        return $this->register($input);
    }

    /**
     * AUTH-7: the email and Google id come from Google, never from the form.
     *
     * @param  array<string, string>  $input
     */
    public function createFromGoogle(array $input, string $googleId, string $email): User
    {
        return $this->register([...$input, 'email' => $email], $googleId);
    }

    /**
     * The sign-up rules. The form shows the same rules to the browser
     * (App\Support\ClientRules), so both check the same things.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(string $type, bool $minor = false, bool $google = false): array
    {
        $academic = $type === 'academic';
        $music = $type === 'music';
        $teacher = $type === 'teacher';

        return [
            'account_type' => ['required', Rule::in(self::accountTypes())],
            ...$this->profileRules(),
            'mobile_country' => $this->mobileCountryRules(),
            'mobile' => $this->mobileRules(),
            'password' => $google ? [] : $this->passwordRules(),
            'age_group' => $music ? ['required', 'in:adult,minor'] : ['prohibited'],
            'adult' => $academic || $teacher || ($music && ! $minor) ? ['accepted'] : ['prohibited'],
            'offerings' => $teacher ? ['required', 'array', 'min:1'] : ['prohibited'],
            'offerings.*' => $teacher ? ['string', Rule::in(Verticals::enabledKeys())] : [],
            'subjects' => $teacher ? ['required', 'string', 'max:300'] : ['prohibited'],
            'teacher_rules' => $teacher ? ['accepted'] : ['prohibited'],
            'guardian_email' => $minor ? ['required', 'string', 'lowercase', 'email', 'max:255', 'different:email'] : ['prohibited'],
            'consent' => ['accepted'],
            'marketing' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The account types people can sign up for.
     *
     * @return list<string>
     */
    public static function accountTypes(): array
    {
        return Verticals::enabled('music') && config('features.batches')
            ? ['parent', 'academic', 'music', 'teacher']
            : ['parent', 'academic', 'teacher'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(string $type): array
    {
        return [
            'adult.accepted' => match ($type) {
                'music' => __('Please confirm you are 18 or older, or choose "Under 18".'),
                'teacher' => __('Teachers and experts must be 18 or older.'),
                default => __('Academic services are for students aged 18 or over. School students join through a parent on our Tuition page.'),
            },
            'offerings.required' => __('Choose at least one section you want to work in.'),
            'subjects.required' => __('Tell us what you teach.'),
            'teacher_rules.accepted' => __('Please agree to the checks and rules to sign up as a teacher.'),
            'age_group.required' => __('Please tell us if you are 18 or older.'),
            'guardian_email.required' => __('Enter your parent\'s or guardian\'s email so they can approve your account.'),
            'guardian_email.different' => __('Your parent\'s email must be different from your own.'),
            'consent.accepted' => __('Please agree to the privacy policy to create an account.'),
        ];
    }

    /**
     * @param  array<string, string>  $input
     */
    private function register(array $input, ?string $googleId = null): User
    {
        $type = $input['account_type'] ?? 'parent';
        $academic = $type === 'academic';
        $music = $type === 'music';
        $teacher = $type === 'teacher';
        $minor = $music && ($input['age_group'] ?? null) === 'minor';

        Validator::make($input, $this->rules($type, $minor, $googleId !== null), $this->messages($type))->validate();

        $user = DB::transaction(function () use ($input, $academic, $music, $teacher, $minor, $googleId) {
            $user = new User([
                'name' => $input['name'],
                'email' => $input['email'],
                ...($googleId === null ? ['password' => $input['password']] : []),
            ]);
            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $googleId === null ? null : now(),
                'role' => match (true) {
                    $academic => Role::AcademicStudent,
                    $music => Role::MusicStudent,
                    $teacher => Role::Teacher,
                    default => Role::Parent,
                },
                // A child's marketing opt-in needs the parent too, so under-18s get none.
                'marketing_opt_in_at' => ! $minor && filter_var($input['marketing'] ?? false, FILTER_VALIDATE_BOOL) ? now() : null,
            ])->save();

            if ($academic) {
                $user->academicStudent()->make(['mobile' => $this->normaliseMobile($input['mobile'], $input['mobile_country'] ?? null)])
                    ->forceFill(['adult_declared_at' => now()])
                    ->save();
                Consent::record($user, ConsentPurpose::AcademicSelf);
            } elseif ($teacher) {
                // HIR-8: waits for the admin's approval (approved_at stays empty).
                /** @var list<string> $offerings */
                $offerings = array_values(array_unique((array) $input['offerings']));
                $user->teacher()->make([
                    'offerings' => $offerings,
                    'subjects' => array_values(array_filter(array_map(fn (string $subject) => Str::limit(trim($subject), 60, ''), explode(',', $input['subjects'])))),
                ])->forceFill(['mobile' => $this->normaliseMobile($input['mobile'], $input['mobile_country'] ?? null)])->save();
                Consent::record($user, ConsentPurpose::TeacherSelf);
            } elseif ($music) {
                // MUS-3: the learner's own profile, without a parent account.
                $learner = $user->student()->make(['first_name' => Str::limit(Str::before(trim($input['name']), ' '), 60, '')]);
                $learner->forceFill([
                    'mobile' => $this->normaliseMobile($input['mobile'], $input['mobile_country'] ?? null),
                    'adult_declared_at' => $minor ? null : now(),
                    'guardian_email' => $minor ? $input['guardian_email'] : null,
                ])->save();
                Consent::record($user, ConsentPurpose::MusicSelf, $learner);
            } else {
                $user->parentProfile()->create(['mobile' => $this->normaliseMobile($input['mobile'], $input['mobile_country'] ?? null)]);
                Consent::record($user, ConsentPurpose::ParentAccount);
            }

            return $user;
        });

        if ($minor) {
            app(SendGuardianConsentRequest::class)->handle($user->student()->firstOrFail());
        }

        if ($teacher && filled(config('site.contact.email'))) {
            Notification::route('mail', config('site.contact.email'))->notify(new NewTeacherSignUp($user));
        }

        return $user;
    }
}
