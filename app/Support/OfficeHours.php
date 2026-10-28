<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * CHAT-5: whether we are in office hours (India time), and when we next open.
 */
final class OfficeHours
{
    public static function isOpen(?CarbonInterface $at = null): bool
    {
        $local = CarbonImmutable::instance($at ?? now())->setTimezone(config('app.display_timezone'));
        $hours = config('chat.office_hours');

        return in_array($local->dayOfWeekIso, $hours['days'], true)
            && $local->format('H:i') >= $hours['opens']
            && $local->format('H:i') < $hours['closes'];
    }

    /**
     * The next opening time in India time (now, if open).
     */
    public static function nextOpening(?CarbonInterface $at = null): CarbonImmutable
    {
        $tz = config('app.display_timezone');
        $local = CarbonImmutable::instance($at ?? now())->setTimezone($tz);
        if (self::isOpen($local)) {
            return $local;
        }

        $hours = config('chat.office_hours');
        for ($day = $local->startOfDay(), $i = 0; $i < 8; $day = $day->addDay(), $i++) {
            $opens = CarbonImmutable::parse($day->toDateString().' '.$hours['opens'], $tz);
            if (in_array($day->dayOfWeekIso, $hours['days'], true) && $opens->gt($local)) {
                return $opens;
            }
        }

        return $local;
    }

    /**
     * For example "Monday to Saturday, 10:00 to 19:00".
     */
    public static function summary(): string
    {
        return (string) config('site.contact.office_hours');
    }
}
