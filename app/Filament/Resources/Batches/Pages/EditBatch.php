<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Actions\Batches\ScheduleSessions;
use App\Enums\ClassSessionStatus;
use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property Batch $record
 */
class EditBatch extends EditRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * A new timetable replaces the future classes that came from the old one.
     * Classes the admin added, moved or cancelled by hand are kept.
     */
    protected function afterSave(): void
    {
        if (! $this->record->wasChanged(['schedule', 'is_active', 'starts_on'])) {
            return;
        }

        $this->record->sessions()
            ->where('starts_at', '>', now())
            ->whereNull('started_at')
            ->where('status', ClassSessionStatus::Scheduled)
            ->whereNotNull('scheduled_for')
            ->where(fn (Builder $query) => $query->whereColumn('starts_at', 'scheduled_for'))
            ->delete();

        app(ScheduleSessions::class)->handle($this->record);
    }
}
