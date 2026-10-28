<?php

namespace Database\Factories;

use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => ParentProfile::factory(),
            'user_id' => User::factory()->student(),
            'first_name' => fake()->firstName(),
            'class_level' => fake()->numberBetween(1, 12),
            'board' => 'CBSE',
            'school' => null,
        ];
    }

    /**
     * A child the parent has added but not yet given a login.
     */
    public function withoutLogin(): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => null]);
    }
}
