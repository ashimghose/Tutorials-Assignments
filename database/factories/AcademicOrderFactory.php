<?php

namespace Database\Factories;

use App\Enums\AcademicLevel;
use App\Models\AcademicOrder;
use App\Models\AcademicStudent;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Needs the order statuses (AcademicCatalogueSeeder).
 *
 * @extends Factory<AcademicOrder>
 */
class AcademicOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_student_id' => AcademicStudent::factory(),
            'service_id' => Service::factory(),
            'service_title' => 'Thesis editing and proofreading',
            'level' => AcademicLevel::Masters,
            'university' => 'University of Calcutta',
            'course' => 'MA English',
            'topic' => 'Partition in Bengali fiction',
            'deadline' => now()->addWeeks(3)->toDateString(),
            'subtotal' => Money::fromRupees(8000),
            'gst_percent' => 18,
            'gst' => Money::fromRupees(1440),
            'total' => Money::fromRupees(9440),
            'order_status_id' => fn () => OrderStatus::initial()->id,
            'revisions_allowed' => 2,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['paid_at' => now(), 'order_status_id' => OrderStatus::afterPayment()->id]);
    }
}
