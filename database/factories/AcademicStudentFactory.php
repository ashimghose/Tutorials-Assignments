<?php

namespace Database\Factories;

use App\Models\AcademicStudent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicStudent>
 */
class AcademicStudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => UserFactory::makeUserWithoutProfile(User::factory()->academicStudent())->id,
            'mobile' => '97'.fake()->numerify('########'),
            'university' => 'University of Delhi',
            'course' => 'M.A. English',
            'level' => 'Masters',
            'stream' => 'Humanities',
            'adult_declared_at' => now(),
        ];
    }
}
