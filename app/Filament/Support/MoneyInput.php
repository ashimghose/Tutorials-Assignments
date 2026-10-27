<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/**
 * A price typed in whole rupees and stored in paise (PAY rule: integers in
 * paise everywhere, never floats).
 */
class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->integer()
            ->minValue(0)
            ->prefix('₹')
            ->formatStateUsing(fn (mixed $state): ?int => is_numeric($state) ? intdiv((int) $state, 100) : null)
            ->dehydrateStateUsing(fn (mixed $state): ?int => is_numeric($state) ? (int) $state * 100 : null);
    }
}
