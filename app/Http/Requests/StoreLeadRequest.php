<?php

namespace App\Http\Requests;

use App\Concerns\AccountValidationRules;
use App\Models\Campaign;
use App\Support\LeadForms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CMS-3: an enquiry, demo booking or waitlist sign-up from a site form or a
 * campaign page. Anyone may send one; the routes are rate-limited.
 */
class StoreLeadRequest extends FormRequest
{
    use AccountValidationRules;

    public function offering(): string
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof Campaign
            ? $campaign->offering
            : LeadForms::offering((string) $this->route('form'), $this->string('section')->toString());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'mobile' => $this->mobileRules(),
            'email' => ['nullable', 'email', 'max:255'],
            'class_or_service' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:2000'],
            'price_range' => ['nullable', 'string', Rule::in(LeadForms::priceRanges($this->offering()))],
            'interest' => ['nullable', 'string', 'max:160'],
            'section' => ['nullable', 'in:tuition,academic,general'],
            'page' => ['nullable', 'string', 'max:120'],
            'marketing_consent' => ['nullable', 'boolean'],
            // Honeypot: hidden from people, filled in by bots.
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile.regex' => __('Enter a 10-digit Indian mobile number.'),
        ];
    }

    public function normalisedMobile(): string
    {
        return $this->normaliseMobile((string) $this->input('mobile'));
    }
}
