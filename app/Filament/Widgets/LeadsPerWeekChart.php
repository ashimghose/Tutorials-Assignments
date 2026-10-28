<?php

namespace App\Filament\Widgets;

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
        $counts = ['tuition' => array_fill(0, 12, 0), 'academic' => array_fill(0, 12, 0)];

        // A few hundred rows at most: grouping in PHP keeps the query portable.
        Lead::query()->where('created_at', '>=', $start->utc())->get(['offering', 'created_at'])
            ->each(function (Lead $lead) use (&$counts, $start, $tz) {
                $week = (int) floor($start->diffInDays($lead->created_at->timezone($tz)) / 7);
                if (isset($counts[$lead->offering][$week])) {
                    $counts[$lead->offering][$week]++;
                }
            });

        return [
            'datasets' => [
                ['label' => 'Tuition', 'data' => $counts['tuition'], 'backgroundColor' => '#b4561a'],
                ['label' => 'Academic', 'data' => $counts['academic'], 'backgroundColor' => '#0b5d45'],
            ],
            'labels' => $weeks->map(fn (CarbonImmutable $week) => $week->format('j M'))->all(),
        ];
    }
}
