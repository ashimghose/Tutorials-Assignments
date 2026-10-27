<?php

namespace App\Filament\Resources\Batches\RelationManagers;

use App\Filament\Resources\Enrolments\EnrolmentResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class EnrolmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrolments';

    protected static ?string $title = 'Students';

    public function table(Table $table): Table
    {
        return EnrolmentResource::enrolmentTable($table, showBatch: false);
    }
}
