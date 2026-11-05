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
use App\Support\Verticals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Self sign-up for parents (AUTH-1), academic students (ACA-3) and music
 * learners (MUS-3). School students never sign up themselves; their parent
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
     * @param  array<string, string>  $input
     */
    private function register(array $input, ?string $googleId = null): User
    {
        $type = $input['account_type'] ?? 'parent';
        $academic = $type === 'academic';
        $music = $type === 'music';
        $minor = $music && ($input['age_group'] ?? null) === 'minor';

        Validator::make($input, [
            'account_type' => ['required', Rule::in(Verticals::enabled('music') && config('features.batches') ? ['parent', 'academic', 'music'] : ['parent', 'academic'])],
            ...$this->profileRules(),
            'mobile' => $this->mobileRules(),
            'password' => $googleId === null ? $this->passwordRules() : [],
            'age_group' => $music ? ['required', 'in:adult,minor'] : ['prohibited'],
            'adult' => $academic || ($music && ! $minor) ? ['accepted'] : ['prohibited'],
            'guardian_email' => $minor ? ['required', 'string', 'lowercase', 'email', 'max:255', 'different:email'] : ['prohibited'],
            'consent' => ['accepted'],
            'marketing' => ['nullable', 'boolean'],
        ], [
            'adult.accepted' => $music
                ? __('Please confirm you are 18 or older, or choose "Under 18".')
                : __('Academic services are for students aged 18 or over. School students join through a parent on our Tuition page.'),
            'age_group.required' => __('Please tell us if you are 18 or older.'),
            'guardian_email.required' => __('Enter your parent\'s or guardian\'s email so they can approve your account.'),
            'guardian_email.different' => __('Your parent\'s email must be different from your own.'),
            'consent.accepted' => __('Please agree to the privacy policy to create an account.'),
            'mobile.regex' => __('Enter a 10-digit Indian mobile number.'),
        ])->validate();

        $user = DB::transaction(function () use ($input, $academic, $music, $minor, $googleId) {
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
                    default => Role::Parent,
                },
                // A child's marketing opt-in needs the parent too, so under-18s get none.
                'marketing_opt_in_at' => ! $minor && filter_var($input['marketing'] ?? false, FILTER_VALIDATE_BOOL) ? now() : null,
            ])->save();

            if ($academic) {
                $user->academicStudent()->make(['mobile' => $this->normaliseMobile($input['mobile'])])
                    ->forceFill(['adult_declared_at' => now()])
                    ->save();
                Consent::record($user, ConsentPurpose::AcademicSelf);
            } elseif ($music) {
                // MUS-3: the learner's own profile, without a parent account.
                $learner = $user->student()->make(['first_name' => Str::limit(Str::before(trim($input['name']), ' '), 60, '')]);
                $learner->forceFill([
                    'mobile' => $this->normaliseMobile($input['mobile']),
                    'adult_declared_at' => $minor ? null : now(),
                    'guardian_email' => $minor ? $input['guardian_email'] : null,
                ])->save();
                Consent::record($user, ConsentPurpose::MusicSelf, $learner);
            } else {
                $user->parentProfile()->create(['mobile' => $this->normaliseMobile($input['mobile'])]);
                Consent::record($user, ConsentPurpose::ParentAccount);
            }

            return $user;
        });

        if ($minor) {
            app(SendGuardianConsentRequest::class)->handle($user->student()->firstOrFail());
        }

        return $user;
    }
}
