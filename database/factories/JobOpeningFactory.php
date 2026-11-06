<?php

namespace Database\Factories;

use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Physics teacher '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => Str::slug($title),
            'offering' => 'tuition',
            'title' => $title,
            'summary' => 'Teach Physics to small online batches.',
            'body' => "## What you will do\n\n- Teach live online classes.",
            'location' => 'Online, from anywhere in Assam',
            'region' => 'Assam',
            'languages' => 'English, Hindi and Assamese',
            'pay' => 'About 50% of each batch\'s monthly fees.',
            'openings' => 1,
            'is_open' => true,
            'posted_on' => now()->subDay()->toDateString(),
            'closes_on' => null,
            'sort_order' => 100,
        ];
    }

    public function closed(): static
    {
        return $this->state(['is_open' => false]);
    }
}
