<?php

namespace App\Actions\Orders;

use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Notifications\QuoteReady;
use App\Services\Pricing;
use Carbon\CarbonInterface;

/**
 * ACA-5: the admin prices a job outside the price list. GST is added on top.
 */
class SendQuote
{
    public function __construct(private Pricing $pricing) {}

    public function handle(AcademicOrder $order, int $amount, CarbonInterface $expiresAt, ?string $note = null): void
    {
        $price = $this->pricing->forQuote($amount, (int) $order->addons()->sum('line_total'));

        $order->forceFill([
            'quote_amount' => $amount,
            'quote_expires_at' => $expiresAt->toDateString(),
            'quote_note' => $note,
            'subtotal' => $price->subtotal,
            'gst_percent' => $price->gstPercent,
            'gst' => $price->gst,
            'total' => $price->total,
        ])->save();

        AuditLog::record('order.quoted', $order);

        $order->academicStudent->user->notify(new QuoteReady($order));
    }
}
