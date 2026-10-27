<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Actions\Batches\ScheduleSessions;
use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Batch $record
 */
class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected function afterCreate(): void
    {
        app(ScheduleSessions::class)->handle($this->record);
    }
}
