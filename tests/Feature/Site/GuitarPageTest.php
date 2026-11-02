<?php

use App\Models\SiteSection;
use App\Support\YouTube;
use Database\Seeders\ContentSeeder;

beforeEach(function () {
    config(['verticals.music.enabled' => true]);
});

test('MUS-1 MUS-2 the music page sells guitar group classes at ₹1,000 a month', function () {
    $this->get('/music')->assertOk()
        ->assertSee('Learn guitar online, live in a small group')
        ->assertSee('Guitar group classes')
        ->assertSee('₹1,000')
        ->assertSee('per learner, per month');
});

test('MUS the music page draws the first four chords as accessible chord boxes', function () {
    $response = $this->get('/music')->assertOk()->assertSee('Four chords that play hundreds of songs');

    foreach (['Em', 'C', 'G', 'D'] as $chord) {
        $response->assertSee('<figcaption class="mt-2 text-lg font-bold text-slate-900">'.$chord.'</figcaption>', false);
    }

    $response->assertSee('aria-label="C chord: low E not played, A fret 3, D fret 2, G open, B fret 1, high e open"', false);
});

test('MUS the video section stays hidden until the admin adds a YouTube link', function () {
    $this->get('/music')->assertDontSee('id="videos"', false);

    $this->seed(ContentSeeder::class);
    config(['features.cms' => true]);
    $section = SiteSection::where('key', 'music')->sole();
    $section->update(['content' => [...$section->content, 'videos' => [
        ['title' => 'A guitar class in 60 seconds', 'url' => 'https://youtu.be/dQw4w9WgXcQ?si=abc', 'caption' => 'Filmed in a real class'],
        ['title' => 'Not a YouTube link', 'url' => 'https://example.com/video.mp4'],
    ]]]);

    $this->get('/music')->assertOk()
        ->assertSee('id="videos"', false)
        ->assertSee('data-youtube="dQw4w9WgXcQ"', false)
        ->assertSee('Play: A guitar class in 60 seconds')
        ->assertSee('Filmed in a real class')
        // Nothing from YouTube loads before the visitor taps play.
        ->assertDontSee('<iframe', false)
        ->assertDontSee('example.com/video.mp4', false)
        ->assertDontSee('Not a YouTube link');
});

test('YouTube links give only an 11-character video id', function (string $link, ?string $id) {
    expect(YouTube::id($link))->toBe($id);
})->with([
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30', 'dQw4w9WgXcQ'],
    ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
    ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    ['https://evil.example/watch?v=dQw4w9WgXcQ', null],
    ['javascript:alert(1)', null],
    ['', null],
]);

test('MKT-6 VRT-1 the beginner guitar guide shows only while music is on', function () {
    $this->get('/resources/starting-guitar-first-month')->assertOk()
        ->assertSee('Starting guitar: what you need and your first month of practice')
        ->assertSee('/images/illustrations/guitar-starter-kit.svg', false)
        ->assertSee('Book a free trial class');
    $this->get('/music')->assertSee('Starting guitar: what you need');

    config(['verticals.music.enabled' => false]);

    $this->get('/resources/starting-guitar-first-month')->assertNotFound();
    $this->get('/resources')->assertDontSee('Starting guitar');
    $this->get('/')->assertDontSee('Starting guitar');
});

test('MUS the guitar content replaces the untouched example music page once', function () {
    $migration = require database_path('migrations/2026_11_02_000002_replace_placeholder_music_content.php');
    config(['features.cms' => true]);

    SiteSection::create(['key' => 'music', 'content' => [
        'hero' => ['title' => $migration::PLACEHOLDER_TITLE, 'lead' => 'Example'],
        'plans' => [['name' => 'Group lessons', 'price' => 150000, 'period' => 'per learner, per month', 'features' => []]],
    ]]);

    $migration->up();

    $this->get('/music')->assertOk()
        ->assertSee('Learn guitar online, live in a small group')
        ->assertSee('₹1,000')
        ->assertDontSee('₹1,500');
});

test('MUS a music page the admin has already edited is left alone', function () {
    $migration = require database_path('migrations/2026_11_02_000002_replace_placeholder_music_content.php');

    $section = SiteSection::create(['key' => 'music', 'content' => [
        'hero' => ['title' => 'Our own guitar classes', 'lead' => 'Edited by the admin'],
    ]]);

    $migration->up();

    expect($section->fresh()->content['hero']['title'])->toBe('Our own guitar classes');
});
