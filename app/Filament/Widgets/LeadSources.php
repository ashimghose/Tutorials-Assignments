<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * MKT-5: which sources bring leads and customers, last 90 days.
 */
class LeadSources extends TableWidget
{
    protected static ?string $heading = 'Where leads came from (last 90 days)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()
                ->selectRaw('MIN(id) as id, utm_source, offering, COUNT(*) as leads, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as customers', [LeadStatus::Converted->value])
                ->where('created_at', '>=', now()->subDays(90))
                ->groupBy('utm_source', 'offering')
                ->orderByDesc('leads'))
            // Rows are groups, so there is no single id to sort by (PostgreSQL rejects it).
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('utm_source')->label('Source')->placeholder('unknown'),
                TextColumn::make('offering')->label('Section')->badge()->color(fn (string $state): string => $state === 'tuition' ? 'warning' : 'primary'),
                TextColumn::make('leads')->numeric(),
                TextColumn::make('customers')->label('Became customers')->numeric(),
            ])
            ->paginated(false);
    }
}
