<?php

namespace App\Actions\Enrolments;

use App\Enums\EnrolmentStatus;
use App\Models\AuditLog;
use App\Models\Enrolment;
use App\Notifications\EnrolmentActivated;
use Carbon\CarbonInterface;

/**
 * Marks the fee as received and gives access until the paid-through date.
 * For now the admin does this after a Payment Link, UPI or cash payment;
 * the payments module will call it after verifying the gateway's signature
 * or webhook (PAY-2), never from the browser.
 */
class ActivateEnrolment
{
    public function handle(Enrolment $enrolment, CarbonInterface $paidThrough, string $source = 'admin'): void
    {
        $enrolment->forceFill([
            'status' => EnrolmentStatus::Active,
            'paid_through' => $paidThrough->toDateString(),
            'removed_at' => null,
            'removed_reason' => null,
            'last_reminder_on' => null,
        ])->save();

        AuditLog::record('enrolment.activated', $enrolment, ['paid_through' => $paidThrough->toDateString(), 'source' => $source]);

        $enrolment->student->parent->user->notify(new EnrolmentActivated($enrolment));
    }
}
