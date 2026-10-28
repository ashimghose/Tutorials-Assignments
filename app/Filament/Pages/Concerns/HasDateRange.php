<?php

namespace App\Filament\Pages\Concerns;

use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;

/**
 * A "from / to" date filter for report pages, in India dates; this month by default.
 */
trait HasDateRange
{
    protected static function periodFilter(): Filter
    {
        $tz = config('app.display_timezone');

        return Filter::make('period')
            ->schema([
                DatePicker::make('from')->default(fn (): string => now($tz)->startOfMonth()->toDateString()),
                DatePicker::make('to')->default(fn (): string => now($tz)->toDateString()),
            ])
            ->columns(2)
            ->columnSpan(2);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected static function period(?array $filters): array
    {
        $tz = config('app.display_timezone');
        $from = $filters['period']['from'] ?? null;
        $to = $filters['period']['to'] ?? null;

        $from = $from ? CarbonImmutable::parse($from, $tz) : CarbonImmutable::now($tz)->startOfMonth();
        $to = $to ? CarbonImmutable::parse($to, $tz) : CarbonImmutable::now($tz);

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }
}
