<?php

namespace App\Filament\Resources\Enrolments;

use App\Enums\EnrolmentStatus;
use App\Filament\Resources\Enrolments\Pages\ListEnrolments;
use App\Models\Enrolment;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Every student's place in every tuition or music batch: new requests
 * waiting for the fee, active, paused and removed.
 */
class EnrolmentResource extends Resource
{
    protected static ?string $model = Enrolment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Tuition';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $pending = Enrolment::query()->where('status', EnrolmentStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return self::enrolmentTable($table, showBatch: true);
    }

    public static function enrolmentTable(Table $table, bool $showBatch): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('student.parent.user', 'student.user', 'batch'))
            ->defaultSort('created_at', 'desc')
            ->columns(array_filter([
                TextColumn::make('student.first_name')->label('Student')->searchable(),
                // MUS-3: a music learner has their own account instead of a parent's.
                TextColumn::make('student.parent.user.name')->label('Parent')->searchable()->placeholder('Own account (music)'),
                TextColumn::make('mobile')->label('Mobile')
                    ->state(fn (Enrolment $record): ?string => $record->student->contactMobile())
                    ->url(fn (Enrolment $record): ?string => $record->student->contactMobile() ? 'https://wa.me/91'.$record->student->contactMobile() : null, shouldOpenInNewTab: true),
                $showBatch ? TextColumn::make('batch_id')->label('Batch')->formatStateUsing(fn (Enrolment $record): string => $record->batch->name()) : null,
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (EnrolmentStatus $state): string => $state->label())
                    ->color(fn (EnrolmentStatus $state): string => $state->color()),
                TextColumn::make('paid_through')->label('Paid until')->date('j M Y')->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('Asked on')->date('j M Y', config('app.display_timezone'))->sortable(),
            ]))
            ->filters([
                SelectFilter::make('status')->options(collect(EnrolmentStatus::cases())->mapWithKeys(fn (EnrolmentStatus $status) => [$status->value => $status->label()])),
                ...($showBatch ? [SelectFilter::make('batch')->relationship('batch', 'subject')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name())] : []),
            ])
            ->recordActions([
                ActionGroup::make([EnrolmentActions::activate(), EnrolmentActions::remove()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnrolments::route('/'),
        ];
    }
}
