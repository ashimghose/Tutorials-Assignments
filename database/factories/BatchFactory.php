<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Teacher;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => 'Maths',
            'class_level' => 10,
            'board' => 'CBSE',
            'teacher_id' => Teacher::factory(),
            'schedule' => [['day' => 1, 'start' => '17:00', 'minutes' => 60], ['day' => 3, 'start' => '17:00', 'minutes' => 60]],
            'max_seats' => 15,
            'monthly_fee' => Money::fromRupees(1500),
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
        ];
    }
}
