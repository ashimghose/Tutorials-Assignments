<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AccountValidationRules
{
    /**
     * An Indian mobile number: 10 digits starting 6 to 9, with an optional
     * +91 or 0 in front. Store it with normaliseMobile().
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mobileRules(): array
    {
        return ['required', 'string', 'regex:/^(?:\+?91[\s-]?|0)?[6-9]\d{4}[\s-]?\d{5}$/'];
    }

    protected function normaliseMobile(string $mobile): string
    {
        return substr(preg_replace('/\D/', '', $mobile), -10);
    }

    /**
     * A school student's login name, chosen by the parent (AUTH-3).
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function usernameRules(?int $ignoreUserId = null): array
    {
        return [
            'required', 'string', 'min:4', 'max:30', 'regex:/^[a-z0-9][a-z0-9._-]*$/',
            Rule::unique(User::class, 'username')->ignore($ignoreUserId),
        ];
    }
}
