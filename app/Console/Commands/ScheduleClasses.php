<?php

namespace App\Console\Commands;

use App\Actions\Batches\ScheduleSessions;
use App\Models\Batch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * CLS-2, run daily: keeps four weeks of classes on every active batch's timetable.
 */
#[Signature('app:schedule-classes')]
#[Description('Add the coming weeks of classes to every active batch')]
class ScheduleClasses extends Command
{
    public function handle(ScheduleSessions $schedule): int
    {
        $created = 0;
        Batch::query()->open()->each(function (Batch $batch) use ($schedule, &$created) {
            $created += $schedule->handle($batch);
        });

        $this->info("Classes added: {$created}.");

        return self::SUCCESS;
    }
}
