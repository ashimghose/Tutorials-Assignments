<?php

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Models\SiteSection;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
});

/**
 * The page as the live site saved it before videos existed: no video list
 * and no video headings.
 */
function withoutVideos(SiteSection $section): void
{
    $content = $section->content;
    unset($content['videos'], $content['text']['videos_eyebrow'], $content['text']['videos_title']);
    $section->update(['content' => $content]);
}

test('MKT-1 the tuition and academic video sections stay hidden until the admin adds a video', function (string $key) {
    withoutVideos(SiteSection::where('key', $key)->sole());

    $this->get(route($key))->assertOk()->assertDontSee('id="videos"', false);
})->with(['tuition', 'academic']);

test('MKT-1 a video added to the tuition or academic page plays only after a tap', function (string $key) {
    $section = SiteSection::where('key', $key)->sole();
    withoutVideos($section);
    $section->update(['content' => [...$section->fresh()->content, 'videos' => [
        ['title' => 'How it works for parents', 'url' => 'https://youtu.be/AXKy0rmT2go', 'caption' => 'For parents'],
        ['title' => 'Not a YouTube link', 'url' => 'https://example.com/video.mp4'],
    ]]]);

    $this->get(route($key))->assertOk()
        ->assertSeeInOrder(['id="how-it-works"', 'id="videos"', 'See how it works'], false)
        ->assertSee('data-youtube="AXKy0rmT2go"', false)
        ->assertSee('Play: How it works for parents')
        ->assertSee("bg-{$key}-800", false)
        ->assertDontSee('<iframe', false)
        ->assertDontSee('Not a YouTube link');
})->with(['tuition', 'academic']);

test('MKT-1 the admin adds a tuition video by pasting its YouTube link', function () {
    $tuition = SiteSection::where('key', 'tuition')->sole();
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $tuition->getRouteKey()])
        ->fillForm(function (array $state) {
            $state['content']['videos'] = [['title' => 'How it works for parents', 'url' => 'https://youtu.be/AXKy0rmT2go', 'caption' => '']];

            return $state;
        })->call('save')->assertHasNoFormErrors();

    expect($tuition->fresh()->content['videos'][0]['url'])->toBe('https://youtu.be/AXKy0rmT2go');
    $this->get(route('tuition'))->assertSee('data-youtube="AXKy0rmT2go"', false);
});

test('MKT-1 the admin cannot save a link that is not a YouTube video', function () {
    $academic = SiteSection::where('key', 'academic')->sole();
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $academic->getRouteKey()])
        ->fillForm(function (array $state) {
            $state['content']['videos'] = [['title' => 'Bad link', 'url' => 'https://example.com/video.mp4', 'caption' => '']];

            return $state;
        })->call('save')->assertHasFormErrors(['content.videos.0.url']);

    expect($academic->fresh()->content['videos'] ?? [])->toBe([]);
});
