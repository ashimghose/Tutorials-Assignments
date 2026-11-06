<?php

namespace App\Http\Requests;

use App\Concerns\AccountValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HIR-2: an application for a job on the Careers pages. Anyone may send
 * one; the route is rate-limited.
 */
class StoreJobApplicationRequest extends FormRequest
{
    use AccountValidationRules;

    public const LANGUAGES = ['English', 'Hindi', 'Assamese', 'Bengali'];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->fieldRules(),
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['string', Rule::in(self::LANGUAGES)],
            'declaration' => ['accepted'],
            // Honeypot: hidden from people, filled in by bots.
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * The fields people type in. The form shows these same rules to the
     * browser, so both check the same things.
     *
     * @return array<string, array<int, mixed>>
     */
    public function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'mobile_country' => $this->mobileCountryRules(),
            'mobile' => $this->mobileRules(),
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'subjects' => ['required', 'string', 'max:300'],
            'qualifications' => ['required', 'string', 'max:500'],
            'experience' => ['required', 'string', 'max:2000'],
            'availability' => ['required', 'string', 'max:200'],
            'link' => ['nullable', 'url:http,https', 'max:500'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'languages.required' => 'Tick the languages you can teach in.',
            'declaration.accepted' => 'Please tick this box to apply.',
            'link.url' => 'Paste a full web link, starting with https://',
        ];
    }

    public function normalisedMobile(): string
    {
        return $this->normaliseMobile((string) $this->input('mobile'), $this->input('mobile_country'));
    }
}
