<?php

namespace App\Filament\Resources\Teachers;

use App\Enums\ClassRatePer;
use App\Enums\Offering;
use App\Enums\OrderRateType;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Support\MoneyInput;
use App\Models\Teacher;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Teachers' and experts' work and public profiles (CMS-1). New teachers are
 * invited from People (AUTH-4).
 */
class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $modelLabel = 'teacher or expert';

    protected static ?string $navigationLabel = 'Teachers and experts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                Select::make('offering')->label('Works in')
                    ->options(collect(Offering::cases())->mapWithKeys(fn (Offering $offering) => [$offering->value => $offering->label()]))
                    ->required(),
                TagsInput::make('subjects'),
            ]),
            Textarea::make('bio')->label('Short public profile')->rows(4)->maxLength(600),
            Grid::make(3)->schema([
                Toggle::make('show_on_site')->label('Show on the website')->inline(false),
                DatePicker::make('active_from'),
                DatePicker::make('active_to')->helperText('Leave empty while they teach with you.'),
            ]),
            // ADM-1: what they are paid, for the monthly payout report (ADM-2).
            Section::make('Pay')->columns(2)->schema([
                MoneyInput::make('class_rate')->label('Pay for classes'),
                Select::make('class_rate_per')->label('Paid')
                    ->options(collect(ClassRatePer::cases())->mapWithKeys(fn (ClassRatePer $per) => [$per->value => $per->label()]))
                    ->default(ClassRatePer::PerClass->value)->selectablePlaceholder(false),
                Select::make('order_rate_type')->label('Pay for academic orders')
                    ->options(collect(OrderRateType::cases())->mapWithKeys(fn (OrderRateType $type) => [$type->value => $type->label()]))
                    ->placeholder('Not paid per order')->live(),
                MoneyInput::make('order_rate')->label('Amount per order')
                    ->visible(fn (Get $get): bool => self::orderRateIs($get('order_rate_type'), OrderRateType::Fixed))
                    ->required(fn (Get $get): bool => self::orderRateIs($get('order_rate_type'), OrderRateType::Fixed)),
                TextInput::make('order_rate')->label('Percentage of the service price')->integer()->minValue(1)->maxValue(100)->suffix('%')
                    ->helperText('Without GST and add-ons such as printing and courier.')
                    ->visible(fn (Get $get): bool => self::orderRateIs($get('order_rate_type'), OrderRateType::Percent))
                    ->required(fn (Get $get): bool => self::orderRateIs($get('order_rate_type'), OrderRateType::Percent)),
            ]),
        ]);
    }

    private static function orderRateIs(mixed $state, OrderRateType $type): bool
    {
        return $state === $type || $state === $type->value;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Name')->searchable(),
                TextColumn::make('offering')->label('Works in')->formatStateUsing(fn (Offering $state): string => $state->label()),
                TextColumn::make('subjects')->badge(),
                IconColumn::make('show_on_site')->label('On website')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachers::route('/'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}
