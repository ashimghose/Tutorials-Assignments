<?php

use App\Models\Service;
use App\Models\ServiceAddon;
use App\Support\SiteContent;
use Illuminate\Database\Migrations\Migration;

/**
 * ACA-1: the owner asked for an "Assignment guidance" row in the academic
 * price list (school to graduation Rs 1,600, post graduate Rs 2,600, PhD
 * Rs 3,600). The live price list is the services table, so the row is added
 * there, first in the list. It is skipped when the table is still empty
 * (AcademicCatalogueSeeder copies the row from the content file then) or
 * when the admin already has an assignment service.
 */
return new class extends Migration
{
    public const TITLE = 'Assignment guidance';

    public function up(): void
    {
        if (! Service::query()->exists()
            || Service::query()->whereRaw('lower(title) like ?', ['%assignment%'])->exists()) {
            return;
        }

        $item = collect(SiteContent::sectionFile('academic')['services'])->firstWhere('name', self::TITLE);

        $service = Service::query()->create([
            'title' => self::TITLE,
            'type' => 'Assignment',
            'description' => $item['body'],
            'price_bachelors' => $item['prices'][0],
            'price_masters' => $item['prices'][1],
            'price_phd' => $item['prices'][2],
            'turnaround' => $item['turnaround'],
            'free_revisions' => $item['revisions'],
            'sort_order' => max(0, (int) Service::query()->min('sort_order') - 10),
        ]);

        $service->addons()->sync(ServiceAddon::query()->pluck('id'));
    }

    public function down(): void
    {
        // The price list is edited in /admin; nothing to undo.
    }
};
