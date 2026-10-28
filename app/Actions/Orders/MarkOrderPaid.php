<?php

namespace App\Actions\Orders;

use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\OrderStatus;
use App\Models\User;

/**
 * Records that the order's payment arrived and moves it out of "Awaiting
 * payment". For now the admin does this after a Payment Link or UPI
 * payment; the payments module will call it after verifying the gateway's
 * signature or webhook (PAY-2), never from the browser.
 */
class MarkOrderPaid
{
    public function __construct(private ChangeOrderStatus $changeStatus) {}

    public function handle(AcademicOrder $order, User $by, string $source = 'admin'): void
    {
        if ($order->isPaid() || $order->total === null) {
            return;
        }

        $order->forceFill(['paid_at' => now()])->save();
        AuditLog::record('order.paid', $order, ['source' => $source]);

        $this->changeStatus->handle($order, OrderStatus::afterPayment(), $by, __('Payment received'));
    }
}
