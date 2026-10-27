<?php

namespace App\Filament\Resources\SiteSections\Pages;

use App\Filament\Resources\SiteSections\SiteSectionResource;
use App\Models\SiteSection;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

/**
 * The academic price list stores each service's prices as a list by level
 * [Bachelors, Masters, PhD]; the form shows them as three fields.
 *
 * @property SiteSection $record
 */
class EditSiteSection extends EditRecord
{
    protected static string $resource = SiteSectionResource::class;

    private const LEVEL_FIELDS = ['price_bachelors', 'price_masters', 'price_phd'];

    public function getTitle(): string
    {
        return $this->record->label();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View on site')
                ->url(fn (): string => route($this->record->key === 'home' ? 'home' : $this->record->key))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($data['content']['services'] ?? [] as $i => $service) {
            foreach (self::LEVEL_FIELDS as $level => $field) {
                $data['content']['services'][$i][$field] = $service['prices'][$level] ?? null;
            }
            unset($data['content']['services'][$i]['prices']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach ($data['content']['services'] ?? [] as $i => $service) {
            $data['content']['services'][$i]['prices'] = array_map(fn (string $field) => $service[$field] ?? null, self::LEVEL_FIELDS);
            $revisions = $service['revisions'] ?? null;
            $data['content']['services'][$i]['revisions'] = is_numeric($revisions) ? (int) $revisions : null;
            foreach (self::LEVEL_FIELDS as $field) {
                unset($data['content']['services'][$i][$field]);
            }
        }

        // Keep keys the form doesn't edit (and merge, so a page's other data survives).
        $data['content'] = array_replace($this->record->content, $data['content']);

        return $data;
    }
}
