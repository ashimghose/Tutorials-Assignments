<?php

namespace App\Actions\Batches;

use App\Enums\ClassSessionStatus;
use App\Models\AuditLog;
use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\User;
use App\Notifications\ClassChanged;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * NOT-1: cancels or moves one class and emails the teacher and the parents
 * of every child who can join it.
 */
class ChangeClass
{
    public function cancel(ClassSession $session): void
    {
        $session->forceFill(['status' => ClassSessionStatus::Cancelled])->save();
        AuditLog::record('class.cancelled', $session);

        Notification::send($this->people($session), new ClassChanged($session, cancelled: true));
    }

    public function move(ClassSession $session, CarbonInterface $startsAt, int $minutes): void
    {
        $session->forceFill([
            'status' => ClassSessionStatus::Scheduled,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($minutes),
        ])->save();
        AuditLog::record('class.moved', $session);

        Notification::send($this->people($session), new ClassChanged($session, cancelled: false));
    }

    /**
     * @return Collection<int, User>
     */
    private function people(ClassSession $session): Collection
    {
        $parents = Enrolment::query()
            ->whereBelongsTo($session->batch)
            ->withAccess()
            ->with('student.parent.user')
            ->get()
            ->map(fn (Enrolment $enrolment) => $enrolment->student->parent->user);

        return $parents->push($session->batch->teacher?->user)->filter()->unique('id')->values();
    }
}
