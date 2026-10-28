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
            'offering' => Offering::Tuition,
            'subjects' => ['Maths'],
        ];
    }
}
