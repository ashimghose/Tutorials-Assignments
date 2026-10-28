<?php

namespace App\Services;

use App\Enums\ClassRatePer;
use App\Enums\ClassSessionStatus;
use App\Enums\OrderRateType;
use App\Models\AcademicOrder;
use App\Models\ClassSession;
use App\Models\Payout;
use App\Models\Teacher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ADM-2, ACA-10: what each teacher and expert earned in a month (India time).
 *
 * - A class counts when it wasn't cancelled, the teacher started it (their
 *   first Join click, or the time the admin entered), and it ran at least
 *   30 minutes: from the start to the recorded end, or to the scheduled end.
 * - Per-hour rates pay the class's scheduled length.
 * - An order counts in the month it was completed, if it was paid and its
 *   status isn't a cancelled one. A percentage is of the service price,
 *   without GST and add-ons such as printing and courier.
 */
class Payouts
{
    public const MIN_CLASS_MINUTES = 30;

    /**
     * @return Collection<int, PayoutLine>
     */
    public function forMonth(CarbonImmutable $month): Collection
    {
        [$from, $to] = self::monthRange($month);
        $paid = Payout::query()->whereDate('month', $from->toDateString())->get()->keyBy('teacher_id');

        return Teacher::query()->with('user')->get()
            ->map(fn (Teacher $teacher) => $this->line($teacher, $month, $paid->get($teacher->id)))
            ->filter(fn (PayoutLine $line) => $line->isPaid() || $line->classes->isNotEmpty() || $line->orders->isNotEmpty())
            ->sortBy(fn (PayoutLine $line) => $line->teacher->user->name)
            ->values();
    }

    public function line(Teacher $teacher, CarbonImmutable $month, ?Payout $payout = null): PayoutLine
    {
        [$from, $to] = self::monthRange($month);

        $classes = ClassSession::query()
            ->whereHas('batch', fn (Builder $q) => $q->whereBelongsTo($teacher))
            ->where('status', '!=', ClassSessionStatus::Cancelled)
            ->whereNotNull('started_at')
            ->where('starts_at', '>=', $from->utc())
            ->where('starts_at', '<', $to->utc())
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (ClassSession $class) => $class->started_at->diffInMinutes($class->ended_at ?? $class->ends_at) >= self::MIN_CLASS_MINUTES)
            ->values();

        $orders = AcademicOrder::query()
            ->where('expert_id', $teacher->id)
            ->whereNotNull('paid_at')
            ->where('completed_at', '>=', $from->utc())
            ->where('completed_at', '<', $to->utc())
            ->whereHas('status', fn (Builder $q) => $q->where('is_cancelled', false))
            ->with('addons')
            ->orderBy('completed_at')
            ->get();

        return new PayoutLine(
            teacher: $teacher,
            classes: $classes,
            classAmount: $classes->sum(fn (ClassSession $class) => $this->classPay($teacher, $class)),
            orders: $orders,
            orderAmount: $orders->sum(fn (AcademicOrder $order) => $this->orderPay($teacher, $order)),
            payout: $payout,
        );
    }

    public function classPay(Teacher $teacher, ClassSession $class): int
    {
        $rate = (int) $teacher->class_rate;

        if ($teacher->class_rate_per === ClassRatePer::PerHour) {
            $minutes = (int) $class->starts_at->diffInMinutes($class->ends_at);

            return intdiv($rate * $minutes + 30, 60);
        }

        return $rate;
    }

    public function orderPay(Teacher $teacher, AcademicOrder $order): int
    {
        return match ($teacher->order_rate_type) {
            OrderRateType::Fixed => (int) $teacher->order_rate,
            OrderRateType::Percent => intdiv(self::servicePrice($order) * (int) $teacher->order_rate + 50, 100),
            null => 0,
        };
    }

    /**
     * The order's price for the work itself: the subtotal without add-ons.
     */
    public static function servicePrice(AcademicOrder $order): int
    {
        return max(0, (int) $order->subtotal - (int) $order->addons->sum('line_total'));
    }

    /**
     * The month's first moment and the next month's, in India time.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function monthRange(CarbonImmutable $month): array
    {
        $tz = config('app.display_timezone');
        $from = CarbonImmutable::parse($month->setTimezone($tz)->format('Y-m').'-01', $tz);

        return [$from, $from->addMonthNoOverflow()];
    }
}
