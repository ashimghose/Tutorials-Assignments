<?php

namespace Database\Factories;

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_opening_id' => JobOpening::factory(),
            'job_title' => 'Physics teacher',
            'offering' => 'tuition',
            'name' => fake()->name(),
            'mobile' => '+9198'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'city' => 'Guwahati',
            'languages' => ['English', 'Hindi', 'Assamese'],
            'subjects' => 'Physics, classes 9 and 10, SEBA',
            'qualifications' => 'MSc Physics, B.Ed',
            'experience' => 'Three years of home tuition.',
            'availability' => 'Weekday evenings',
            'declared_at' => now(),
            'status' => JobApplicationStatus::New,
        ];
    }
}
