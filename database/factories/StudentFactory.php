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
     * MUS-3: a music learner with their own account. Adult unless $adult is
     * false, in which case a parent's approval is still awaited.
     */
    public function musicLearner(bool $adult = true): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'user_id' => User::factory()->musicStudent(),
            'class_level' => null,
            'board' => null,
            'mobile' => '9876543210',
            'adult_declared_at' => $adult ? now() : null,
            'guardian_email' => $adult ? null : 'parent@example.com',
            'guardian_consented_at' => null,
        ]);
    }

    /**
     * A child the parent has added but not yet given a login.
     */
    public function withoutLogin(): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => null]);
    }
}
