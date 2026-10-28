<?php

namespace App\Actions\Enrolments;

use App\Enums\EnrolmentStatus;
use App\Models\AuditLog;
use App\Models\Enrolment;
use App\Notifications\RemovedFromBatch;

/**
 * CLS-7: ends a child's place in a batch. The parent is told why.
 */
class RemoveFromBatch
{
    public function handle(Enrolment $enrolment, string $reason): void
    {
        $enrolment->forceFill([
            'status' => EnrolmentStatus::Removed,
            'removed_at' => now(),
            'removed_reason' => $reason,
        ])->save();

        AuditLog::record('enrolment.removed', $enrolment);

        $enrolment->student->parent->user->notify(new RemovedFromBatch($enrolment));
    }
}
