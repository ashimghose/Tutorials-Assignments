<?php

namespace App\Actions\Money;

use App\Models\Payout;
use App\Models\User;
use App\Services\PayoutLine;
use App\Services\Payouts;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * ADM-2: records that a teacher's or expert's month was paid, keeping the
 * classes, orders and amounts as calculated now.
 */
class MarkPayoutPaid
{
    public function handle(PayoutLine $line, CarbonImmutable $month, CarbonInterface $paidOn, ?string $reference, User $by): Payout
    {
        if ($line->isPaid()) {
            throw new InvalidArgumentException('This month is already marked as paid.');
        }

        [$from] = Payouts::monthRange($month);

        $payout = new Payout;
        $payout->forceFill([
            'teacher_id' => $line->teacher->id,
            'month' => $from->toDateString(),
            'classes' => $line->classes->count(),
            'class_amount' => $line->classAmount,
            'orders' => $line->orders->count(),
            'order_amount' => $line->orderAmount,
            'total' => $line->total(),
            'paid_on' => $paidOn->toDateString(),
            'reference' => $reference,
            'recorded_by' => $by->id,
        ])->save();

        return $payout;
    }
}
