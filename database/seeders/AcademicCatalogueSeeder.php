<?php

namespace Database\Seeders;

use App\Enums\AddonKind;
use App\Enums\AddonUnit;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\SiteSection;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * M6: copies the academic price list and add-ons from the site content
 * (the admin's edited version if there is one) into their own tables, and
 * adds the default order statuses (ACA-6). Runs only on empty tables, so it
 * never overwrites the admin's changes.
 */
class AcademicCatalogueSeeder extends Seeder
{
    /**
     * ACA-6 default list: name, colour, notify student, final, expert may set, revision, tracking.
     */
    private const STATUSES = [
        ['Awaiting payment', 'warning', false, false, false, false, false],
        ['Not picked yet', 'gray', true, false, false, false, false],
        ['Assigned', 'info', false, false, false, false, false],
        ['In progress', 'info', false, false, true, false, false],
        ['Draft shared', 'primary', true, false, true, false, false],
        ['Revision requested', 'warning', false, false, false, true, false],
        ['Complete', 'success', true, true, true, false, false],
        ['Printed', 'primary', false, false, false, false, false],
        ['Shipped', 'primary', true, false, false, false, true],
        ['Delivered', 'success', true, true, false, false, false],
        ['Cancelled', 'danger', true, true, false, false, false],
    ];

    public function run(): void
    {
        if (! OrderStatus::query()->exists()) {
            foreach (self::STATUSES as $i => [$name, $color, $notify, $final, $expert, $revision, $tracking]) {
                OrderStatus::query()->create([
                    'name' => $name,
                    'sort_order' => ($i + 1) * 10,
                    'color' => $color,
                    'notify_student' => $notify,
                    'is_final' => $final,
                    'expert_can_set' => $expert,
                    'is_revision' => $revision,
                    'needs_tracking' => $tracking,
                ]);
            }
        }

        if (Service::query()->exists()) {
            return;
        }

        $content = SiteSection::query()->where('key', 'academic')->value('content') ?? SiteContent::sectionFile('academic');
        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        /** @var list<array{name: string, price: int, unit: string}> $addonRows */
        $addonRows = $content['addons'] ?? [];
        $addons = collect($addonRows)->values()->map(fn (array $addon, int $i) => ServiceAddon::query()->create([
            'name' => $addon['name'],
            'kind' => $this->addonKind($addon['name']),
            'unit' => str_contains(strtolower((string) $addon['unit']), 'copy') ? AddonUnit::PerCopy : AddonUnit::PerOrder,
            'price' => (int) $addon['price'],
            'sort_order' => ($i + 1) * 10,
        ]));

        foreach (array_values($content['services'] ?? []) as $i => $item) {
            $prices = array_values($item['prices'] ?? []);
            $service = Service::query()->create([
                'title' => $item['name'],
                'type' => $this->serviceType($item['name']),
                'description' => $item['body'] ?? null,
                'price_bachelors' => $prices[0] ?? null,
                'price_masters' => $prices[1] ?? null,
                'price_phd' => $prices[2] ?? null,
                'turnaround' => $item['turnaround'] ?? null,
                'free_revisions' => (int) ($item['revisions'] ?? 0),
                'sort_order' => ($i + 1) * 10,
            ]);
            $service->addons()->sync($addons->pluck('id'));
        }
    }

    private function addonKind(string $name): AddonKind
    {
        return match (true) {
            Str::contains($name, ['bound', 'print', 'copy'], ignoreCase: true) => AddonKind::Printing,
            Str::contains($name, 'courier', ignoreCase: true) => AddonKind::Courier,
            Str::contains($name, 'express', ignoreCase: true) => AddonKind::Express,
            default => AddonKind::Other,
        };
    }

    private function serviceType(string $title): string
    {
        return match (true) {
            Str::contains($title, 'synopsis', ignoreCase: true) => 'Synopsis',
            Str::contains($title, 'research paper', ignoreCase: true) => 'Research paper',
            Str::contains($title, 'research book', ignoreCase: true) => 'Research book',
            Str::contains($title, ['thesis', 'dissertation'], ignoreCase: true) => 'Thesis or dissertation',
            default => 'Any document',
        };
    }
}
