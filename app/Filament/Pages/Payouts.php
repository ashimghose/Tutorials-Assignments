<?php

namespace App\Filament\Pages;

use App\Actions\Money\MarkPayoutPaid;
use App\Enums\ClassRatePer;
use App\Enums\OrderRateType;
use App\Models\Teacher;
use App\Services\PayoutLine;
use App\Services\Payouts as PayoutCalculator;
use App\Support\Money;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * ADM-2: each teacher's and expert's classes taken and completed orders for
 * a month, at their rates, and "Mark paid" with the date and reference.
 */
class Payouts extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static ?string $title = 'Teacher payouts';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters): array => $this->lines(self::month($filters))
                ->mapWithKeys(fn (PayoutLine $line) => [$line->teacher->id => self::row($line)])
                ->all())
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('rates')->color('gray')->wrap(),
                TextColumn::make('classes')->label('Classes taken')->numeric(),
                TextColumn::make('class_amount')->label('For classes')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('orders')->label('Orders completed')->numeric(),
                TextColumn::make('order_amount')->label('For orders')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('total')->label('Amount due')->weight('bold')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('paid')->label('Paid')->placeholder('Not yet')->badge()->color('success'),
            ])
            ->filters([
                SelectFilter::make('month')->options(self::monthOptions())->default(now(config('app.display_timezone'))->format('Y-m'))->selectablePlaceholder(false),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (array $record): bool => $record['paid'] === null && $record['total'] > 0)
                    ->modalDescription(fn (array $record): string => 'Record that '.Money::format($record['total']).' was paid to '.$record['name'].'. The classes, orders and amounts are kept as they are now.')
                    ->schema([
                        DatePicker::make('paid_on')->required()->default(fn (): string => now(config('app.display_timezone'))->toDateString()),
                        TextInput::make('reference')->label('Reference (UTR or note)')->maxLength(100),
                    ])
                    ->action(function (array $record, array $data, MarkPayoutPaid $markPaid): void {
                        $month = self::month($this->tableFilters);
                        $line = app(PayoutCalculator::class)->line(Teacher::query()->findOrFail((int) $record['teacher_id']), $month);
                        $markPaid->handle($line, $month, CarbonImmutable::parse($data['paid_on']), $data['reference'] ?: null, Auth::user());
                        $this->flushCachedTableRecords();
                        Notification::make()->success()->title('Marked as paid')->send();
                    }),
            ])
            ->emptyStateHeading('No classes or completed orders this month')
            ->paginated(false);
    }

    /**
     * @return Collection<int, PayoutLine>
     */
    private function lines(CarbonImmutable $month)
    {
        return app(PayoutCalculator::class)->forMonth($month);
    }

    /**
     * @return array<string, mixed>
     */
    private static function row(PayoutLine $line): array
    {
        $teacher = $line->teacher;
        $payout = $line->payout;
        $rates = array_filter([
            $teacher->class_rate !== null ? Money::format($teacher->class_rate).' '.($teacher->class_rate_per ?? ClassRatePer::PerClass)->label() : null,
            match ($teacher->order_rate_type) {
                OrderRateType::Fixed => Money::format((int) $teacher->order_rate).' per order',
                OrderRateType::Percent => $teacher->order_rate.'% per order',
                null => null,
            },
        ]);

        // Once paid, show what was paid.
        return [
            'teacher_id' => $teacher->id,
            'name' => $teacher->user->name,
            'rates' => $rates === [] ? 'No rate set' : implode(', ', $rates),
            'classes' => $payout->classes ?? $line->classes->count(),
            'class_amount' => $payout->class_amount ?? $line->classAmount,
            'orders' => $payout->orders ?? $line->orders->count(),
            'order_amount' => $payout->order_amount ?? $line->orderAmount,
            'total' => $payout->total ?? $line->total(),
            'paid' => $payout ? $payout->paid_on->format('j M Y').($payout->reference ? " · {$payout->reference}" : '') : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    private static function month(?array $filters): CarbonImmutable
    {
        $value = $filters['month']['value'] ?? null;
        $tz = config('app.display_timezone');

        return is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value)
            ? CarbonImmutable::parse("{$value}-01", $tz)
            : CarbonImmutable::now($tz)->startOfMonth();
    }

    /**
     * This month and the eleven before it.
     *
     * @return array<string, string>
     */
    private static function monthOptions(): array
    {
        $first = CarbonImmutable::now(config('app.display_timezone'))->startOfMonth();

        return collect(range(0, 11))->mapWithKeys(function (int $i) use ($first) {
            $month = $first->subMonthsNoOverflow($i);

            return [$month->format('Y-m') => $month->format('F Y')];
        })->all();
    }
}
