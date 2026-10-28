<?php

namespace Database\Factories;

use App\Enums\LeadKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => LeadKind::Enquiry,
            'offering' => 'tuition',
            'name' => fake()->name(),
            'mobile' => '98'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'interest' => 'General enquiry',
            'status' => LeadStatus::New,
            'marketing_consent' => false,
        ];
    }
}
