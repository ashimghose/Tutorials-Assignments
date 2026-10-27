<?php

namespace App\Filament\Resources\Expenses;

use App\Enums\ExpenseCategory;
use App\Enums\Offering;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Support\MoneyInput;
use App\Models\Expense;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * ADM-4: printing, courier and other costs. Order costs can also be added
 * from the order's page.
 */
class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationLabel = 'Costs';

    protected static ?string $modelLabel = 'cost';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                Select::make('offering')->label('Section')->options([Offering::Tuition->value => 'Tuition', Offering::Academic->value => 'Academic services'])->required(),
                Select::make('category')->options(ExpenseCategory::options())->required(),
                MoneyInput::make('amount')->required()->minValue(1),
                DatePicker::make('spent_on')->required()->default(fn (): string => now(config('app.display_timezone'))->toDateString()),
            ]),
            TextInput::make('note')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('spent_on', 'desc')
            ->columns([
                TextColumn::make('spent_on')->date('j M Y')->sortable(),
                TextColumn::make('offering')->label('Section')->badge()->formatStateUsing(fn (Offering $state): string => $state->label())
                    ->color(fn (Offering $state): string => $state === Offering::Tuition ? 'warning' : 'primary'),
                TextColumn::make('category')->formatStateUsing(fn (ExpenseCategory $state): string => $state->label()),
                TextColumn::make('order')->state(fn (Expense $record): ?string => $record->order?->reference())->placeholder('—'),
                TextColumn::make('amount')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('note')->placeholder('—')->limit(40),
            ])
            ->filters([
                SelectFilter::make('offering')->label('Section')->options([Offering::Tuition->value => 'Tuition', Offering::Academic->value => 'Academic services']),
                SelectFilter::make('category')->options(ExpenseCategory::options()),
            ])
            ->headerActions([
                CreateAction::make()->using(function (array $data): Expense {
                    $expense = new Expense($data);
                    $expense->forceFill(['recorded_by' => Auth::id()])->save();

                    return $expense;
                }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
        ];
    }
}
