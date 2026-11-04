<?php

use App\Models\SiteSection;
use App\Support\SiteContent;

function academicHeadlineMigration(): object
{
    return require database_path('migrations/2026_11_03_100001_shorten_academic_headline.php');
}

test('MKT-1 the academic page shows a short headline with the services as badges', function () {
    $response = $this->get('/academic')->assertOk()
        ->assertSee('<h1 class="mt-2 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">Assignments, thesis, dissertation and research paper support</h1>', false)
        ->assertSee('aria-label="What we offer"', false)
        ->assertSee('Turnitin plagiarism and AI check', false);

    foreach (SiteContent::sectionFile('academic')['keywords'] as $keyword) {
        $response->assertSee('>'.e($keyword).'</li>', false);
    }
});

test('MKT-1 tuition and music show no badges until the admin adds some', function (string $path) {
    config(['verticals.music.enabled' => true]);

    $this->get($path)->assertOk()->assertDontSee('aria-label="What we offer"', false);
})->with(['/tuition', '/music']);

test('MKT-1 the long academic headline on the live page is replaced, with badges added', function () {
    config(['features.cms' => true]);
    $migration = academicHeadlineMigration();

    $content = SiteContent::sectionFile('academic');
    $content['hero']['title'] = 'Content of assignments, thesis, dissertation or research paper; editing; formatting; printing; & Handwritten';
    $content['text']['seo_title'] = $migration::OLD_SEO_TITLE;
    unset($content['keywords'], $content['experts'], $content['testimonials']);
    SiteSection::create(['key' => 'academic', 'content' => $content]);

    $migration->up();

    $this->get('/academic')
        ->assertDontSee('Content of assignments')
        ->assertSee('>'.$migration::NEW_TITLE.'</h1>', false)
        ->assertSee('<title>'.$migration::NEW_SEO_TITLE, false)
        ->assertSee('Handwritten copy of your own work');
});

test('MKT-1 a headline the admin has since changed is left alone', function () {
    $migration = academicHeadlineMigration();

    $section = SiteSection::create(['key' => 'academic', 'content' => [
        'hero' => ['title' => 'Our own headline', 'lead' => 'Lead'],
        'text' => ['seo_title' => 'Our own title'],
    ]]);

    $migration->up();

    expect($section->fresh()->content['hero']['title'])->toBe('Our own headline')
        ->and($section->fresh()->content['text']['seo_title'])->toBe('Our own title');
});
