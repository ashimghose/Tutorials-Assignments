<?php

namespace App\Filament\Support;

use App\Enums\Offering;

/**
 * The switched-on sections (VRT-1), for selects and filters.
 */
class OfferingOptions
{
    /**
     * @return array<string, string>
     */
    public static function sections(): array
    {
        return Offering::sectionOptions();
    }
}
