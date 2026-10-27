<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\MoneyInput;
use App\Models\Service;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
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
 * ACA-1: the academic price list. Prices are typed in rupees and stored in
 * paise; an empty price means the job is quoted at that level.
 */
class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('title')->required()->maxLength(120),
                TextInput::make('type')->required()->maxLength(60)->placeholder('Thesis or dissertation')
                    ->datalist(fn () => Service::query()->distinct()->orderBy('type')->pluck('type')->all()),
            ]),
            Textarea::make('description')->rows(3)->helperText('Guidance, editing, formatting, checking or printing only: never writing the student\'s work for them.'),
            TagsInput::make('includes')->label('What\'s included'),
            Grid::make(3)->schema([
                MoneyInput::make('price_bachelors')->label('Bachelors price')->helperText('Empty = quote only'),
                MoneyInput::make('price_masters')->label('Masters price'),
                MoneyInput::make('price_phd')->label('PhD price'),
            ]),
            Grid::make(3)->schema([
                TextInput::make('turnaround')->maxLength(60)->placeholder('5 working days'),
                TextInput::make('free_revisions')->integer()->minValue(0)->maxValue(10)->default(0)->required(),
                TextInput::make('sort_order')->label('Order on the page')->integer()->default(0),
            ]),
            CheckboxList::make('addons')->label('Add-ons offered with this service')
                ->relationship('addons', 'name')->columns(2),
            Toggle::make('is_active')->label('On sale')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $price = fn (?int $state): string => $state === null ? 'Quote' : Money::format($state);

        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('type')->toggleable(),
                TextColumn::make('price_bachelors')->label('Bachelors')->formatStateUsing($price)->default('Quote'),
                TextColumn::make('price_masters')->label('Masters')->formatStateUsing($price)->default('Quote'),
                TextColumn::make('price_phd')->label('PhD')->formatStateUsing($price)->default('Quote'),
                IconColumn::make('is_active')->label('On sale')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
