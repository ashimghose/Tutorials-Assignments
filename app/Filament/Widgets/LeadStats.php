<?php

namespace App\Filament\Widgets;

use App\Enums\LeadKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * MKT-5: this week's interest per offering, run separately because each
 * offering passes its gate on its own.
 */
class LeadStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $since = now()->subDays(7);

        $count = fn (string $offering) => Lead::query()->where('offering', $offering)->where('created_at', '>=', $since)->count();
        $waitlist = Lead::query()->where('kind', LeadKind::Waitlist)->count();
        $total = Lead::query()->where('created_at', '>=', now()->subDays(90))->count();
        $converted = Lead::query()->where('created_at', '>=', now()->subDays(90))->where('status', LeadStatus::Converted)->count();

        return [
            Stat::make('Tuition leads, last 7 days', $count('tuition')),
            Stat::make('Academic leads, last 7 days', $count('academic')),
            Stat::make('On waitlists', $waitlist),
            Stat::make('Became customers, last 90 days', $total > 0 ? round($converted / $total * 100).'%' : '—')
                ->description("{$converted} of {$total} leads"),
        ];
    }
}
