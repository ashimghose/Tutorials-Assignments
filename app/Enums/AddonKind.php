<?php

namespace App\Enums;

enum AddonKind: string
{
    case Printing = 'printing';
    case Courier = 'courier';
    case Express = 'express';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Printing => 'Printing and binding',
            self::Courier => 'Courier',
            self::Express => 'Express turnaround',
            self::Other => 'Other',
        };
    }

    /**
     * Printing and courier need a delivery address; nothing else does.
     */
    public function needsAddress(): bool
    {
        return in_array($this, [self::Printing, self::Courier], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])->all();
    }
}
