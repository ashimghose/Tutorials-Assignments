<?php

namespace App\Filament\Resources\JobApplications\Pages;

use App\Enums\JobApplicationStatus;
use App\Filament\Resources\JobApplications\JobApplicationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewJobApplication extends ViewRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            JobApplicationResource::inviteAction(),
            ...collect([JobApplicationStatus::Shortlisted, JobApplicationStatus::Hired, JobApplicationStatus::NotSelected])
                ->map(fn (JobApplicationStatus $status) => JobApplicationResource::statusAction($status))
                ->all(),
        ];
    }
}
