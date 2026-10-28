<?php

namespace App\Enums;

enum AddonUnit: string
{
    case PerCopy = 'per_copy';
    case PerOrder = 'per_order';

    public function label(): string
    {
        return match ($this) {
            self::PerCopy => 'per copy',
            self::PerOrder => 'per order',
        };
    }
}
