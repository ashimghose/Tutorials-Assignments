<?php

namespace App\Filament\Resources\Teachers;

use App\Enums\Offering;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\Teacher;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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
        ]);
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
