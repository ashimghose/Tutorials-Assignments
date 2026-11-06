<?php

namespace Database\Factories;

use App\Enums\Offering;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => UserFactory::makeUserWithoutProfile(User::factory()->teacher())->id,
            'offerings' => [Offering::Tuition->value],
            'subjects' => ['Maths'],
            'approved_at' => now(),
        ];
    }

    /**
     * HIR-8: signed up themselves and not approved yet.
     */
    public function pending(): static
    {
        return $this->state(['approved_at' => null]);
    }
}
