<?php

namespace App\Filament\Resources\Batches;

use App\Enums\EnrolmentStatus;
use App\Enums\Offering;
use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\RelationManagers\EnrolmentsRelationManager;
use App\Filament\Resources\Batches\RelationManagers\SessionsRelationManager;
use App\Filament\Support\MoneyInput;
use App\Models\Batch;
use App\Models\Teacher;
use App\Support\Money;
use App\Support\SiteContent;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * CLS-1: tuition batches with their weekly schedule (India time), teacher,
 * seats, monthly fee and Google Meet link.
 */
class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Tuition';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $boards = SiteContent::section('tuition')['boards'];

        return $schema->columns(1)->components([
            Section::make('Batch')->columns(3)->schema([
                TextInput::make('subject')->required()->maxLength(80)->placeholder('Maths'),
                Select::make('class_level')->label('Class')->required()
                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $level) => [$level => "Class {$level}"])),
                Select::make('board')->required()->options(array_combine([...$boards, 'Other'], [...$boards, 'Other'])),
                Select::make('teacher_id')->label('Teacher')
                    ->options(fn () => Teacher::query()->whereIn('offering', [Offering::Tuition, Offering::Both])->with('user')->get()
                        ->mapWithKeys(fn (Teacher $teacher) => [$teacher->id => $teacher->user->name])),
                MoneyInput::make('monthly_fee')->label('Monthly fee')->required(),
                TextInput::make('max_seats')->label('Seats')->integer()->minValue(1)->maxValue(50)->default(15)->required(),
            ]),
            Section::make('Timetable')->description('Times are India time. Classes for the next four weeks are added automatically every day.')->schema([
                Repeater::make('schedule')->label('Weekly classes')->required()->minItems(1)->columns(3)->defaultItems(1)
                    ->addActionLabel('Add a weekly class')
                    ->schema([
                        Select::make('day')->options(Batch::DAYS)->required(),
                        TimePicker::make('start')->seconds(false)->required(),
                        TextInput::make('minutes')->label('Length (minutes)')->integer()->minValue(15)->maxValue(240)->default(60)->required(),
                    ]),
                Grid::make(3)->schema([
                    DatePicker::make('starts_on')->label('First class from')->helperText('Leave empty to start now.'),
                    TextInput::make('meet_link')->label('Google Meet link')->url()->maxLength(255)->placeholder('https://meet.google.com/…'),
                    Toggle::make('record_by_default')->label('Record classes')->inline(false)->helperText('Used from Phase 3.'),
                ]),
                Toggle::make('is_active')->label('Taking students and running classes')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('teacher.user')
                ->withCount(['enrolments as seats_taken' => fn ($query) => $query->whereIn('status', EnrolmentStatus::holdingSeat())]))
            ->defaultSort('class_level')
            ->columns([
                TextColumn::make('class_level')->label('Class')->sortable(),
                TextColumn::make('subject')->searchable(),
                TextColumn::make('board'),
                TextColumn::make('teacher.user.name')->label('Teacher')->placeholder('Not assigned'),
                TextColumn::make('schedule')->label('Timetable')->state(fn (Batch $record): string => $record->scheduleSummary())->wrap(),
                TextColumn::make('seats_taken')->label('Seats')->state(fn (Batch $record): string => "{$record->getAttribute('seats_taken')} / {$record->max_seats}"),
                TextColumn::make('monthly_fee')->label('Fee')->formatStateUsing(fn (int $state): string => Money::format($state)),
                IconColumn::make('is_active')->label('Running')->boolean(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Running')->default(true)])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            SessionsRelationManager::class,
            EnrolmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatches::route('/'),
            'create' => CreateBatch::route('/create'),
            'edit' => EditBatch::route('/{record}/edit'),
        ];
    }
}
