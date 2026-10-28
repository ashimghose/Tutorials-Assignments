<?php

namespace App\Services;

use App\Models\AcademicOrder;
use App\Models\ClassSession;
use App\Models\Payout;
use App\Models\Teacher;
use Illuminate\Support\Collection;

/**
 * One teacher's or expert's payout for a month (ADM-2).
 */
final readonly class PayoutLine
{
    /**
     * @param  Collection<int, ClassSession>  $classes  classes that started and ran at least 30 minutes
     * @param  Collection<int, AcademicOrder>  $orders  paid orders completed this month
     */
    public function __construct(
        public Teacher $teacher,
        public Collection $classes,
        public int $classAmount,
        public Collection $orders,
        public int $orderAmount,
        public ?Payout $payout,
    ) {}

    public function total(): int
    {
        return $this->classAmount + $this->orderAmount;
    }

    public function isPaid(): bool
    {
        return $this->payout !== null;
    }
}
