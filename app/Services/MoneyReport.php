<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\Offering;
use App\Models\AcademicOrder;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Payout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * ADM-4: money in and out for a date range (India dates, both ends
 * included), split by offering. Teacher payouts count by the date they were
 * paid: classes to Tuition, orders to Academic.
 */
class MoneyReport
{
    public const ROWS = [
        'received' => 'Received',
        'gateway_fees' => 'Gateway fees',
        'paid_out' => 'Paid to teachers and experts',
        'printing_courier' => 'Printing and courier',
        'other_costs' => 'Other costs',
        'net' => 'Net',
    ];

    /**
     * @return array<string, array<string, int>> offering => row => paise, plus a "total" column
     */
    public function between(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];
        $payments = Payment::query()->whereBetween('received_on', $range)->get();
        $expenses = Expense::query()->whereBetween('spent_on', $range)->get();
        $payouts = Payout::query()->whereBetween('paid_on', $range)->get();

        $report = [];
        foreach ([Offering::Tuition, Offering::Academic] as $offering) {
            $in = $payments->where('offering', $offering);
            $costs = $expenses->where('offering', $offering);
            $row = [
                'received' => (int) $in->sum('amount'),
                'gateway_fees' => (int) $in->sum('gateway_fee'),
                'paid_out' => (int) $payouts->sum($offering === Offering::Tuition ? 'class_amount' : 'order_amount'),
                'printing_courier' => (int) $costs->whereIn('category', [ExpenseCategory::Printing, ExpenseCategory::Courier])->sum('amount'),
                'other_costs' => (int) $costs->where('category', ExpenseCategory::Other)->sum('amount'),
            ];
            $row['net'] = $row['received'] - $row['gateway_fees'] - $row['paid_out'] - $row['printing_courier'] - $row['other_costs'];
            $report[$offering->value] = $row;
        }

        $report['total'] = collect(array_keys(self::ROWS))
            ->mapWithKeys(fn (string $key) => [$key => $report['tuition'][$key] + $report['academic'][$key]])->all();

        return $report;
    }

    /**
     * ADM-4: academic orders placed in the range, by status, service and expert.
     *
     * @return array{status: Collection<string, int>, service: Collection<string, int>, expert: Collection<string, int>, value: int}
     */
    public function orders(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $tz = config('app.display_timezone');
        $orders = AcademicOrder::query()
            ->where('created_at', '>=', CarbonImmutable::parse($from->toDateString(), $tz)->utc())
            ->where('created_at', '<', CarbonImmutable::parse($to->toDateString(), $tz)->addDay()->utc())
            ->with('status', 'expert.user')
            ->get();

        return [
            'status' => $orders->countBy(fn (AcademicOrder $order) => $order->status->name)->sortDesc(),
            'service' => $orders->countBy('service_title')->sortDesc(),
            'expert' => $orders->countBy(fn (AcademicOrder $order) => $order->expert?->user->name ?? 'Not assigned')->sortDesc(),
            'value' => (int) $orders->whereNotNull('paid_at')->sum('total'),
        ];
    }
}
