<?php

namespace App\Enums;

/**
 * ADM-1: a teacher is paid per class taken or per hour taught.
 */
enum ClassRatePer: string
{
    case PerClass = 'class';
    case PerHour = 'hour';

    public function label(): string
    {
        return match ($this) {
            self::PerClass => 'per class',
            self::PerHour => 'per hour',
        };
    }
}
