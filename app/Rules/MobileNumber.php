<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A mobile number for the country picked next to it (Google libphonenumber).
 * The browser runs the same check (resources/js/phone-field.js).
 */
class MobileNumber implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(public readonly string $countryField = 'mobile_country') {}

    public static function message(): string
    {
        return __('Enter a valid mobile number for the country you chose.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $country = data_get($this->data, $this->countryField);

        if (! is_string($value) || ! Phone::isValid($value, is_string($country) ? $country : null)) {
            $fail(self::message());
        }
    }
}
