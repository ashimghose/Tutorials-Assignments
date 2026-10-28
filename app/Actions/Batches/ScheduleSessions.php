<?php

namespace App\Actions\Batches;

use App\Models\Batch;
use Carbon\CarbonImmutable;

/**
 * CLS-2: adds the batch's weekly classes for the coming weeks. Safe to run
 * again: a slot that already has a class (even a moved or cancelled one) is
 * skipped.
 */
class ScheduleSessions
{
    public const DAYS_AHEAD = 28;

    public function handle(Batch $batch, int $days = self::DAYS_AHEAD): int
    {
        if (! $batch->is_active) {
            return 0;
        }

        $tz = config('app.display_timezone');
        $from = CarbonImmutable::now($tz);
        if ($batch->starts_on !== null && $batch->starts_on->toDateString() > $from->toDateString()) {
            $from = CarbonImmutable::parse($batch->starts_on->toDateString(), $tz);
        }
        $to = CarbonImmutable::now($tz)->addDays($days)->endOfDay();

        $created = 0;
        foreach ($batch->slotsBetween($from, $to) as $slot) {
            if ($batch->sessions()->where('scheduled_for', $slot['starts_at'])->exists()) {
                continue;
            }
            $batch->sessions()->make()->forceFill([
                'scheduled_for' => $slot['starts_at'],
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
                'recording_on' => $batch->record_by_default,
            ])->save();
            $created++;
        }

        return $created;
    }
}
