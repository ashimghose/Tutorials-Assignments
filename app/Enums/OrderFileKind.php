<?php

namespace App\Enums;

enum OrderFileKind: string
{
    case Brief = 'brief';
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Brief => 'From the student',
            self::Draft => 'Draft',
            self::Final => 'Final',
        };
    }
}
