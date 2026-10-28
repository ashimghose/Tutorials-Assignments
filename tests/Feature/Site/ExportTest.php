<?php

use Illuminate\Support\Facades\Storage;
use Spatie\Export\Exporter;

beforeEach(function () {
    Storage::fake('export');
    config(['export.disk' => 'export']);
});

test('CMS-2 every public page exports to static HTML', function () {
    app(Exporter::class)->export();

    $disk = Storage::disk('export');

    foreach ([
        'index.html',
        'tuition/index.html',
        'academic/index.html',
        'resources/index.html',
        'resources/how-to-structure-a-synopsis/index.html',
        'about/index.html',
        'contact/index.html',
        'privacy/index.html',
        'terms/index.html',
        'refund-policy/index.html',
        'sitemap.xml',
        '404.html',
        'favicon.ico',
        'favicon.svg',
        'images/brand/indian-assignments-logo.svg',
        'images/brand/indian-assignments-icon.svg',
        'images/illustrations/home-hero.svg',
        'images/illustrations/tuition-live-class.svg',
        'images/illustrations/academic-services.svg',
        'apple-touch-icon.png',
        'robots.txt',
    ] as $file) {
        expect($disk->exists($file))->toBeTrue("{$file} was not exported");
    }

    expect($disk->get('tuition/index.html'))->toContain('Live online tuition for classes 1 to 12');
});

test('the site icon is the Indian Assignments icon, square and without text', function () {
    $svg = file_get_contents(public_path('favicon.svg'));

    expect($svg)->toContain('#075B45')
        ->and($svg)->toContain('#C9570B')
        ->and($svg)->toContain('viewBox="166 149 924 924"')
        ->and($svg)->not->toContain('<text')
        ->and($svg)->not->toContain('<script')
        ->and($svg)->not->toContain('#FF2D20');
});

test('the export leaves out PHP files and pages behind a login', function () {
    // The static export is the Phase 0–1 site, built with the Phase 2 modules off.
    config(['features.academic_orders' => false, 'features.batches' => false, 'features.cms' => false, 'features.chat' => false]);

    app(Exporter::class)->export();

    $disk = Storage::disk('export');

    expect($disk->exists('index.php'))->toBeFalse()
        ->and($disk->exists('login/index.html'))->toBeFalse()
        ->and($disk->exists('dashboard/index.html'))->toBeFalse();
});

test('the 404 page tells search engines not to index it', function () {
    $this->get('/404.html')
        ->assertOk()
        ->assertSee('Page not found')
        ->assertSee('<meta name="robots" content="noindex">', false);

    $this->get('/tuition')->assertDontSee('name="robots"', false);
});
