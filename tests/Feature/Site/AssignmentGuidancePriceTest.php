<?php

use App\Models\Service;
use App\Models\ServiceAddon;
use Database\Seeders\AcademicCatalogueSeeder;

function assignmentPriceMigration(): object
{
    return require database_path('migrations/2026_11_04_200001_add_assignment_guidance_price.php');
}

test('ACA-1 the price list shows assignment guidance at Rs 1,600, 2,600 and 3,600', function () {
    $this->get('/academic')->assertOk()
        ->assertSeeInOrder(['id="prices"', 'Assignment guidance', '₹1,600', '₹2,600', '₹3,600', 'Synopsis guidance'], false)
        ->assertSee('You write it; we guide you.');
});

test('ACA-1 the live price list gets the assignment row first, editable in admin', function () {
    config(['features.academic_orders' => true]);
    $addon = ServiceAddon::factory()->create();
    Service::factory()->create(['title' => 'Synopsis guidance and review', 'sort_order' => 10]);

    assignmentPriceMigration()->up();

    $service = Service::query()->where('title', 'Assignment guidance')->sole();
    expect($service->price_bachelors)->toBe(160000)
        ->and($service->price_masters)->toBe(260000)
        ->and($service->price_phd)->toBe(360000)
        ->and($service->sort_order)->toBe(0)
        ->and($service->addons->pluck('id')->all())->toBe([$addon->id]);

    $this->get('/academic')->assertSeeInOrder(['Assignment guidance', '₹1,600', '₹2,600', '₹3,600', 'Synopsis guidance and review'], false);
});

test('ACA-1 the migration leaves an admin assignment service alone and never runs twice', function () {
    Service::factory()->create(['title' => 'Assignment help', 'price_bachelors' => 99900]);

    assignmentPriceMigration()->up();
    assignmentPriceMigration()->up();

    expect(Service::query()->count())->toBe(1);
});

test('ACA-1 a fresh install gets the assignment row from the seeder instead', function () {
    assignmentPriceMigration()->up();
    expect(Service::query()->exists())->toBeFalse();

    $this->seed(AcademicCatalogueSeeder::class);

    expect(Service::query()->orderBy('sort_order')->first())
        ->title->toBe('Assignment guidance')
        ->type->toBe('Assignment')
        ->price_bachelors->toBe(160000);
});
