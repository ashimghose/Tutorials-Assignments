<?php

namespace App\Filament\Resources\Payments;

use App\Enums\Offering;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Support\MoneyInput;
use App\Models\Payment;
use App\Support\Money;
use BackedEnum;
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

/**
 * ADM-4: money received. Rows come from "Fee received" on an enrolment and
 * "Payment received" on an order; here the admin can check and correct them.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Money received';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                MoneyInput::make('amount')->required()->minValue(1),
                DatePicker::make('received_on')->required(),
                Select::make('method')->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [$m->value => $m->label()]))->required(),
                TextInput::make('reference')->maxLength(100),
                MoneyInput::make('gateway_fee')->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('order', 'enrolment.student', 'enrolment.batch'))
            ->defaultSort('received_on', 'desc')
            ->columns([
                TextColumn::make('received_on')->date('j M Y')->sortable(),
                TextColumn::make('offering')->badge()->formatStateUsing(fn (Offering $state): string => $state->label())
                    ->color(fn (Offering $state): string => $state === Offering::Tuition ? 'warning' : 'primary'),
                TextColumn::make('for')->state(fn (Payment $record): string => $record->description()),
                TextColumn::make('amount')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('gateway_fee')->label('Fee')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('method')->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextColumn::make('reference')->placeholder('—')->searchable(),
            ])
            ->filters([
                SelectFilter::make('offering')->options([Offering::Tuition->value => 'Tuition', Offering::Academic->value => 'Academic services']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
