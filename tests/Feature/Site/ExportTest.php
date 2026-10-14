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
        'robots.txt',
    ] as $file) {
        expect($disk->exists($file))->toBeTrue("{$file} was not exported");
    }

    expect($disk->get('tuition/index.html'))->toContain('Live online tuition for classes 1 to 12');
});

test('the export leaves out PHP files and pages behind a login', function () {
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
