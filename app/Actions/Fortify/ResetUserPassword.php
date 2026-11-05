<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Also used by the teacher invitation, and shown to the browser by the
     * forms (App\Support\ClientRules).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['password' => $this->passwordRules()];
    }

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, $this->rules())->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
