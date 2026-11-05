<?php

namespace App\Concerns;

use App\Models\User;
use App\Rules\MobileNumber;
use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AccountValidationRules
{
    /**
     * A mobile number for the country picked next to it, checked with
     * Google's libphonenumber. Store it with normaliseMobile().
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mobileRules(bool $required = true, string $countryField = 'mobile_country'): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:30', new MobileNumber($countryField)];
    }

    /**
     * The country of a mobile number, as an ISO 3166 code. Left out means India.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mobileCountryRules(): array
    {
        return ['nullable', 'string', Rule::in(Phone::countries())];
    }

    /**
     * The number in E.164 form, e.g. +919876543210.
     */
    protected function normaliseMobile(string $mobile, ?string $country = null): string
    {
        return Phone::e164($mobile, $country) ?? $mobile;
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
