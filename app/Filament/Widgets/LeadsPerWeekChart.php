<?php

namespace App\Filament\Widgets;

use App\Enums\Offering;
use App\Models\Lead;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

/**
 * MKT-5: leads per week by offering, last 12 weeks (India time).
 */
class LeadsPerWeekChart extends ChartWidget
{
    protected ?string $heading = 'Leads per week';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $tz = (string) config('app.display_timezone');
        $start = CarbonImmutable::now($tz)->startOfWeek()->subWeeks(11);

        $weeks = collect(range(0, 11))->map(fn (int $i) => $start->addWeeks($i));
        $counts = collect(Offering::sectionValues())->mapWithKeys(fn (string $section) => [$section => array_fill(0, 12, 0)])->all();
        $colours = ['tuition' => '#b4561a', 'academic' => '#0b5d45', 'music' => '#5b3a8c'];

        // A few hundred rows at most: grouping in PHP keeps the query portable.
        Lead::query()->where('created_at', '>=', $start->utc())->get(['offering', 'created_at'])
            ->each(function (Lead $lead) use (&$counts, $start, $tz) {
                $week = (int) floor($start->diffInDays($lead->created_at->timezone($tz)) / 7);
                if (isset($counts[$lead->offering][$week])) {
                    $counts[$lead->offering][$week]++;
                }
            });

        return [
            'datasets' => collect(Offering::sections())->map(fn (Offering $offering) => [
                'label' => $offering->label(),
                'data' => $counts[$offering->value],
                'backgroundColor' => $colours[$offering->value] ?? '#64748b',
            ])->all(),
            'labels' => $weeks->map(fn (CarbonImmutable $week) => $week->format('j M'))->all(),
        ];
    }
}
