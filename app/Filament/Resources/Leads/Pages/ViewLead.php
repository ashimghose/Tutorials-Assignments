<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return collect([LeadStatus::Contacted, LeadStatus::Converted, LeadStatus::Closed])
            ->map(fn (LeadStatus $status) => LeadResource::statusAction($status))
            ->all();
    }
}
