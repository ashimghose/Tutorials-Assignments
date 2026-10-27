<?php

namespace App\Filament\Resources\ServiceAddons;

use App\Enums\AddonKind;
use App\Enums\AddonUnit;
use App\Filament\Resources\ServiceAddons\Pages\CreateServiceAddon;
use App\Filament\Resources\ServiceAddons\Pages\EditServiceAddon;
use App\Filament\Resources\ServiceAddons\Pages\ListServiceAddons;
use App\Filament\Support\MoneyInput;
use App\Models\ServiceAddon;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * ACA-2: printing, binding, courier and express add-ons. Printing and
 * courier ask the student for a delivery address.
 */
class ServiceAddonResource extends Resource
{
    protected static ?string $model = ServiceAddon::class;

    protected static ?string $modelLabel = 'add-on';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(80),
                Select::make('kind')->options(AddonKind::options())->required()
                    ->helperText('Printing and courier ask for a delivery address.'),
                MoneyInput::make('price')->required(),
                Select::make('unit')->options([AddonUnit::PerCopy->value => 'Per copy', AddonUnit::PerOrder->value => 'Per order'])->required(),
            ]),
            Toggle::make('is_active')->label('On sale')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('kind')->formatStateUsing(fn (AddonKind $state): string => $state->label()),
                TextColumn::make('price')->formatStateUsing(fn (int $state, ServiceAddon $record): string => Money::format($state).' '.$record->unit->label()),
                IconColumn::make('is_active')->label('On sale')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceAddons::route('/'),
            'create' => CreateServiceAddon::route('/create'),
            'edit' => EditServiceAddon::route('/{record}/edit'),
        ];
    }
}
