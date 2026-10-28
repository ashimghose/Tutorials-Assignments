<?php

namespace App\Console\Commands;

use App\Enums\EnrolmentStatus;
use App\Models\Enrolment;
use App\Notifications\AccessPaused;
use App\Notifications\FeeReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * PAY-4, run daily: emails fee reminders 5 days and 1 day before the
 * paid-through date, and pauses enrolments 3 days after it.
 */
#[Signature('app:check-enrolments')]
#[Description('Send fee reminders and pause enrolments whose grace period has ended')]
class CheckEnrolments extends Command
{
    public function handle(): int
    {
        $today = Enrolment::today();
        $reminded = 0;
        $paused = 0;

        foreach (Enrolment::REMINDER_DAYS as $days) {
            Enrolment::query()
                ->where('status', EnrolmentStatus::Active)
                ->whereDate('paid_through', $today->addDays($days)->toDateString())
                ->where(fn ($query) => $query->whereNull('last_reminder_on')->orWhereDate('last_reminder_on', '<', $today->toDateString()))
                ->with('student.parent.user', 'batch')
                ->each(function (Enrolment $enrolment) use ($days, $today, &$reminded) {
                    $enrolment->student->parent->user->notify(new FeeReminder($enrolment, $days));
                    $enrolment->forceFill(['last_reminder_on' => $today->toDateString()])->save();
                    $reminded++;
                });
        }

        Enrolment::query()
            ->where('status', EnrolmentStatus::Active)
            ->whereDate('paid_through', '<', $today->subDays(Enrolment::GRACE_DAYS)->toDateString())
            ->with('student.parent.user', 'batch')
            ->each(function (Enrolment $enrolment) use (&$paused) {
                $enrolment->forceFill(['status' => EnrolmentStatus::Expired])->save();
                $enrolment->student->parent->user->notify(new AccessPaused($enrolment));
                $paused++;
            });

        $this->info("Reminders sent: {$reminded}. Enrolments paused: {$paused}.");

        return self::SUCCESS;
    }
}
