<?php

namespace App\Actions\Orders;

use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\OrderStatus;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ACA-6, ACA-7: moves an order to another status, keeps the history and
 * emails the student when the status is set to notify. The caller decides
 * who may set which status (AcademicOrderPolicy).
 */
class ChangeOrderStatus
{
    public function handle(AcademicOrder $order, OrderStatus $to, User $by, ?string $note = null, ?string $courier = null, ?string $tracking = null): void
    {
        // PAY-2: an order leaves "Awaiting payment" only once paid (or by being cancelled).
        if (! $order->isPaid() && ! $to->is_final && ! $to->isInitial()) {
            throw ValidationException::withMessages(['status' => __('This order is not paid yet.')]);
        }

        if ($to->needs_tracking && (blank($courier) || blank($tracking))) {
            throw ValidationException::withMessages(['tracking_number' => __('Enter the courier and tracking number.')]);
        }

        DB::transaction(function () use ($order, $to, $by, $note, $courier, $tracking) {
            $from = $order->order_status_id;

            $order->forceFill(['order_status_id' => $to->id]);
            if ($to->needs_tracking) {
                $order->forceFill(['courier_name' => $courier, 'tracking_number' => $tracking]);
            }
            if ($to->is_final) {
                $order->forceFill(['completed_at' => $order->completed_at ?? now()]);
                // Privacy: files are kept for the retention period after the order closes.
                $order->files()->whereNull('delete_after')->update(['delete_after' => now()->addDays((int) config('academic.retention_days'))]);
            }
            $order->save();

            $order->history()->make()->forceFill([
                'from_status_id' => $from,
                'to_status_id' => $to->id,
                'changed_by' => $by->id,
                'note' => $note,
                'created_at' => now(),
            ])->save();
        });

        $order->setRelation('status', $to);

        if ($by->isAdmin()) {
            AuditLog::record('order.status', $order, ['status' => $to->name]);
        }

        if ($to->notify_student) {
            $order->academicStudent->user->notify(new OrderStatusChanged($order, $note));
        }
    }
}
