<?php

namespace Database\Factories;

use App\Enums\AddonKind;
use App\Enums\AddonUnit;
use App\Models\ServiceAddon;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceAddon>
 */
class ServiceAddonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Hard bound copy',
            'kind' => AddonKind::Printing,
            'unit' => AddonUnit::PerCopy,
            'price' => Money::fromRupees(600),
        ];
    }

    public function courier(): static
    {
        return $this->state(fn () => ['name' => 'Courier anywhere in India', 'kind' => AddonKind::Courier, 'unit' => AddonUnit::PerOrder, 'price' => Money::fromRupees(250)]);
    }

    public function express(): static
    {
        return $this->state(fn () => ['name' => 'Express turnaround', 'kind' => AddonKind::Express, 'unit' => AddonUnit::PerOrder, 'price' => Money::fromRupees(1000)]);
    }
}
