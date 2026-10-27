<?php

namespace App\Filament\Support;

/**
 * The two marketing sections, for selects and filters.
 */
class OfferingOptions
{
    /**
     * @return array<string, string>
     */
    public static function sections(): array
    {
        return ['tuition' => 'Tuition', 'academic' => 'Academic services'];
    }
}
