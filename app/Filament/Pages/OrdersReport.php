<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasDateRange;
use App\Services\MoneyReport;
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
 * ADM-4: academic orders placed in a date range, by status, service and expert.
 */
class OrdersReport extends Page implements HasTable
{
    use HasDateRange, InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static ?string $title = 'Academic orders report';

    protected static ?string $navigationLabel = 'Orders report';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?array $filters): array {
                [$from, $to] = self::period($filters);
                $report = app(MoneyReport::class)->orders($from, $to);

                $rows = [];
                foreach (['status' => 'By status', 'service' => 'By service', 'expert' => 'By expert'] as $key => $heading) {
                    foreach ($report[$key] as $name => $count) {
                        $rows["{$key}-{$name}"] = ['by' => $heading, 'name' => $name, 'orders' => $count];
                    }
                }

                return $rows;
            })
            ->columns([
                TextColumn::make('by')->label('')->color('gray'),
                TextColumn::make('name')->label(''),
                TextColumn::make('orders')->numeric()->alignEnd(),
            ])
            ->filters([self::periodFilter()], layout: FiltersLayout::AboveContent)
            ->emptyStateHeading('No orders in these dates')
            ->paginated(false);
    }
}
