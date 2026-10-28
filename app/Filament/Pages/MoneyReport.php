<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasDateRange;
use App\Services\MoneyReport as Report;
use App\Support\Money;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * ADM-4: money received, paid to teachers and experts, gateway fees and
 * printing and courier costs for a date range, split by offering, with the net.
 */
class MoneyReport extends Page implements HasTable
{
    use HasDateRange, InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static ?string $title = 'Money report';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        $money = fn (int $state): string => Money::format($state);

        return $table
            ->records(function (?array $filters): array {
                [$from, $to] = self::period($filters);
                $report = app(Report::class)->between($from, $to);

                return collect(Report::ROWS)->mapWithKeys(fn (string $label, string $key) => [$key => [
                    'row' => $label,
                    'tuition' => $report['tuition'][$key],
                    'academic' => $report['academic'][$key],
                    'total' => $report['total'][$key],
                ]])->all();
            })
            ->columns([
                TextColumn::make('row')->label('')->weight(fn (array $record): string => $record['row'] === 'Net' ? 'bold' : 'normal'),
                TextColumn::make('tuition')->label('Tuition')->formatStateUsing($money)->alignEnd(),
                TextColumn::make('academic')->label('Academic services')->formatStateUsing($money)->alignEnd(),
                TextColumn::make('total')->label('Total')->formatStateUsing($money)->alignEnd()->weight('bold'),
            ])
            ->filters([self::periodFilter()], layout: FiltersLayout::AboveContent)
            ->paginated(false);
    }
}
