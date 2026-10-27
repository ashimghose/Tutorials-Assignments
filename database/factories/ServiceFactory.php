<?php

namespace Database\Factories;

use App\Models\Service;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Thesis editing and proofreading',
            'type' => 'Thesis or dissertation',
            'description' => 'Chapter-by-chapter language editing.',
            'price_bachelors' => Money::fromRupees(5000),
            'price_masters' => Money::fromRupees(8000),
            'price_phd' => Money::fromRupees(15000),
            'turnaround' => '10 working days',
            'free_revisions' => 2,
        ];
    }

    public function quoted(): static
    {
        return $this->state(fn () => ['price_bachelors' => null, 'price_masters' => null, 'price_phd' => null]);
    }
}
