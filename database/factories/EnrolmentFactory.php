<?php

namespace Database\Factories;

use App\Enums\EnrolmentStatus;
use App\Models\Batch;
use App\Models\Enrolment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrolment>
 */
class EnrolmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'batch_id' => Batch::factory(),
            'status' => EnrolmentStatus::Pending,
        ];
    }

    /**
     * Paid through the given number of days from today in India.
     */
    public function active(int $paidForDays = 20): static
    {
        return $this->state(fn () => [
            'status' => EnrolmentStatus::Active,
            'paid_through' => Enrolment::today()->addDays($paidForDays)->toDateString(),
        ]);
    }
}
