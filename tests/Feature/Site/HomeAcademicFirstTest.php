<?php

use App\Models\SiteSection;
use App\Support\SiteContent;

function academicFirstMigration(): object
{
    return require database_path('migrations/2026_11_14_100001_put_academic_first_on_home.php');
}

test('MKT-1 the home page title, description and headline lead with Academic', function () {
    $text = SiteContent::sectionFile('home')['text'];

    $this->get('/')->assertOk()
        ->assertSee('<title>Assignment and thesis guidance, plus tuition and music | ', false)
        ->assertSee('<meta name="description" content="Guidance, editing and formatting for your own assignments', false)
        ->assertSee('<meta property="og:description" content="Guidance, editing and formatting for your own assignments', false)
        ->assertSeeInOrder(['assignments', 'tuition', 'guitar'])
        ->assertSee($text['footer']);
});

test('MKT-1 text saved already escaped is escaped only once on the page', function () {
    config(['features.cms' => true]);

    $home = SiteContent::sectionFile('home');
    $home['text']['seo_title'] = 'Students&#39; own work';
    $home['text']['seo_description'] = 'Help with students&#39; own work &amp; more.';
    SiteSection::create(['key' => 'home', 'content' => $home]);

    $this->get('/')->assertOk()
        ->assertSee('<title>Students&#039; own work | ', false)
        ->assertSee('<meta name="description" content="Help with students&#039; own work &amp; more.">', false)
        ->assertDontSee('&amp;#39;', false)
        ->assertDontSee('&amp;amp;', false)
        ->assertSee('"description":"Help with students\' own work & more."', false);
});

test('MKT-1 the live home page gets the Academic-first text, also when saved escaped', function () {
    config(['features.cms' => true]);
    $migration = academicFirstMigration();

    $home = SiteContent::sectionFile('home');
    foreach ($migration::CHANGES as $path => [$old]) {
        data_set($home, $path, $old);
    }
    $home['text']['seo_description'] = str_replace("'", '&#39;', $home['text']['seo_description']);
    $row = SiteSection::create(['key' => 'home', 'content' => $home]);

    $migration->up();

    foreach ($migration::CHANGES as $path => [, $new]) {
        expect(data_get($row->fresh()->content, $path))->toBe($new);
    }

    $this->get('/')->assertSee('<title>'.$migration::CHANGES['text.seo_title'][1].' | ', false);
});

test('MKT-1 home text the admin has since changed is left alone', function () {
    $migration = academicFirstMigration();

    $row = SiteSection::create(['key' => 'home', 'content' => [
        'hero' => ['title' => 'Our own headline', 'lead' => 'Our own lead'],
        'text' => ['seo_title' => 'Our own title', 'seo_description' => 'Our own description', 'footer' => 'Our own footer'],
    ]]);

    $migration->up();

    expect($row->fresh()->content['hero'])->toBe(['title' => 'Our own headline', 'lead' => 'Our own lead'])
        ->and($row->fresh()->content['text'])->toBe(['seo_title' => 'Our own title', 'seo_description' => 'Our own description', 'footer' => 'Our own footer']);
});
