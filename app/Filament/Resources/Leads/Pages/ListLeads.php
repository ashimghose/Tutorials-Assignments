<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\AuditLog;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CMS-3: the email marketing list holds only people who ticked the box.
            Action::make('exportMarketingList')
                ->label('Download marketing list')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): StreamedResponse {
                    AuditLog::record('leads.marketing_export');

                    return response()->streamDownload(function (): void {
                        $out = fopen('php://output', 'w');
                        if ($out === false) {
                            return;
                        }
                        fputcsv($out, ['name', 'email', 'mobile', 'section', 'signed_up']);
                        Lead::query()->where('marketing_consent', true)->orderBy('id')
                            ->each(fn (Lead $lead) => fputcsv($out, [$lead->name, $lead->email, $lead->mobile, $lead->offering, $lead->created_at?->toDateString()]));
                        fclose($out);
                    }, 'marketing-list-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
                }),
        ];
    }
}
