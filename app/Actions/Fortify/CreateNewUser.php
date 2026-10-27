<?php

namespace App\Actions\Fortify;

use App\Concerns\AccountValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Self sign-up for parents (AUTH-1) and academic students (ACA-3). School
 * students never sign up themselves; their parent adds them.
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
        $academic = ($input['account_type'] ?? 'parent') === 'academic';

        Validator::make($input, [
            'account_type' => ['required', 'in:parent,academic'],
            ...$this->profileRules(),
            'mobile' => $this->mobileRules(),
            'password' => $this->passwordRules(),
            'adult' => $academic ? ['accepted'] : ['prohibited'],
            'consent' => ['accepted'],
        ], [
            'adult.accepted' => __('Academic services are for students aged 18 or over. School students join through a parent on our Tuition page.'),
            'consent.accepted' => __('Please agree to the privacy policy to create an account.'),
            'mobile.regex' => __('Enter a 10-digit Indian mobile number.'),
        ])->validate();

        return DB::transaction(function () use ($input, $academic) {
            $user = new User([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
            $user->forceFill(['role' => $academic ? Role::AcademicStudent : Role::Parent])->save();

            if ($academic) {
                $user->academicStudent()->make(['mobile' => $this->normaliseMobile($input['mobile'])])
                    ->forceFill(['adult_declared_at' => now()])
                    ->save();
                Consent::record($user, ConsentPurpose::AcademicSelf);
            } else {
                $user->parentProfile()->create(['mobile' => $this->normaliseMobile($input['mobile'])]);
                Consent::record($user, ConsentPurpose::ParentAccount);
            }

            return $user;
        });
    }
}
