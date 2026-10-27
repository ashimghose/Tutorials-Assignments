<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDay()->startOfHour();

        return [
            'batch_id' => Batch::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
        ];
    }

    /**
     * A class starting in the given number of minutes (negative: already started).
     */
    public function startingIn(int $minutes, int $length = 60): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addMinutes($minutes),
            'ends_at' => now()->addMinutes($minutes + $length),
        ]);
    }
}
