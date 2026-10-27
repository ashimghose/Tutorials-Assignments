<?php

namespace App\Filament\Resources\Batches\RelationManagers;

use App\Actions\Batches\ChangeClass;
use App\Actions\Batches\ScheduleSessions;
use App\Enums\ClassSessionStatus;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\ClassSession;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CLS-2: the batch's classes. Weekly classes are added from the timetable;
 * the admin adds extra ones, moves or cancels a class, and the teacher and
 * parents are emailed.
 */
class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'Classes';

    public function table(Table $table): Table
    {
        $tz = config('app.display_timezone');

        return $table
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')->label('Starts')->dateTime('D j M Y, g:i a', $tz)->sortable(),
                TextColumn::make('ends_at')->label('Ends')->dateTime('g:i a', $tz),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (ClassSessionStatus $state): string => $state->label())
                    ->color(fn (ClassSessionStatus $state): string => $state === ClassSessionStatus::Cancelled ? 'gray' : 'success'),
                TextColumn::make('started_at')->label('Teacher joined')->dateTime('g:i a', $tz)->placeholder('—'),
                TextColumn::make('ended_at')->label('Ended')->dateTime('g:i a', $tz)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('recording_on')->label('Record')->boolean(),
            ])
            ->filters([
                Filter::make('upcoming')->label('Upcoming only')->default()
                    ->query(fn (Builder $query) => $query->where('ends_at', '>=', now())),
            ])
            ->headerActions([
                Action::make('schedule')
                    ->label('Add the next four weeks')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->action(function (ScheduleSessions $schedule): void {
                        /** @var Batch $batch */
                        $batch = $this->getOwnerRecord();
                        $count = $schedule->handle($batch);
                        Notification::make()->success()->title("{$count} classes added")->send();
                    }),
                Action::make('extra')
                    ->label('Add an extra class')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema(self::timeFields())
                    ->action(function (array $data): void {
                        /** @var Batch $batch */
                        $batch = $this->getOwnerRecord();
                        $start = CarbonImmutable::parse($data['starts_at']);
                        $session = new ClassSession(['recording_on' => $batch->record_by_default]);
                        $session->forceFill(['starts_at' => $start, 'ends_at' => $start->addMinutes((int) $data['minutes'])]);
                        $batch->sessions()->save($session);
                    }),
            ])
            ->recordActions([
                Action::make('move')
                    ->label('Move')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema(self::timeFields())
                    ->fillForm(fn (ClassSession $record): array => [
                        'starts_at' => $record->starts_at,
                        'minutes' => (int) $record->starts_at->diffInMinutes($record->ends_at),
                    ])
                    ->action(fn (ClassSession $record, array $data, ChangeClass $change) => $change->move($record, CarbonImmutable::parse($data['starts_at']), (int) $data['minutes'])),
                // ADM-2: fix the record when the teacher forgot to press Join, or the class ended early.
                Action::make('times')
                    ->label('Class taken')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->visible(fn (ClassSession $record): bool => ! $record->isCancelled() && $record->starts_at->isPast())
                    ->modalDescription('Classes count for the teacher\'s pay when they started and ran at least 30 minutes.')
                    ->fillForm(fn (ClassSession $record): array => ['started_at' => $record->started_at, 'ended_at' => $record->ended_at])
                    ->schema([
                        DateTimePicker::make('started_at')->label('Started (India time)')->seconds(false)->timezone(config('app.display_timezone')),
                        DateTimePicker::make('ended_at')->label('Ended (India time, if early)')->seconds(false)->timezone(config('app.display_timezone'))->after('started_at'),
                    ])
                    ->action(function (ClassSession $record, array $data): void {
                        $record->forceFill(['started_at' => $data['started_at'] ?: null, 'ended_at' => $data['ended_at'] ?: null])->save();
                        AuditLog::record('class.times_recorded', $record);
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('The teacher and parents are emailed.')
                    ->visible(fn (ClassSession $record): bool => ! $record->isCancelled())
                    ->action(fn (ClassSession $record, ChangeClass $change) => $change->cancel($record)),
            ]);
    }

    /**
     * @return array<int, DateTimePicker|TextInput>
     */
    private static function timeFields(): array
    {
        return [
            DateTimePicker::make('starts_at')->label('Starts (India time)')->seconds(false)->timezone(config('app.display_timezone'))->required(),
            TextInput::make('minutes')->label('Length (minutes)')->integer()->minValue(15)->maxValue(240)->default(60)->required(),
        ];
    }
}
