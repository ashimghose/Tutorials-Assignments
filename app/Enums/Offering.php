<?php

namespace App\Enums;

/**
 * The business's two offerings. Teachers work in one or both.
 */
enum Offering: string
{
    case Tuition = 'tuition';
    case Academic = 'academic';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Tuition => 'Tuition',
            self::Academic => 'Academic services',
            self::Both => 'Both',
        };
    }
}
