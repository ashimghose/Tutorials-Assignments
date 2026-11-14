<?php

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Models\SiteSection;
use App\Models\User;
use App\Support\YouTube;
use Database\Seeders\ContentSeeder;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
});

/**
 * The tuition page as the live site saved it before sample classes existed:
 * no list and no headings (the headings come from the content file).
 */
function tuitionWithoutSamples(): SiteSection
{
    $section = SiteSection::where('key', 'tuition')->sole();
    $content = $section->content;
    unset($content['samples'], $content['text']['samples_eyebrow'], $content['text']['samples_title'],
        $content['text']['samples_lead'], $content['text']['samples_button']);
    $section->update(['content' => $content]);

    return $section->fresh();
}

test('MKT-1 the sample classes section stays hidden until the admin adds a video', function () {
    tuitionWithoutSamples();

    $this->get(route('tuition'))->assertOk()->assertDontSee('id="sample-classes"', false);
});

test('MKT-1 sample classes show before the fees, Shorts as tall cards, and play only after a tap', function () {
    $section = tuitionWithoutSamples();
    $section->update(['content' => [...$section->content, 'samples' => [
        ['title' => 'Fractions made easy', 'url' => 'https://youtu.be/mPaxGO480bY?si=6i_rzZIDjysvXZAX', 'class' => '6', 'subject' => 'Maths'],
        ['title' => 'Light and shadows', 'url' => 'https://youtube.com/shorts/Yzcf6-dT8ow?si=Fr-01H9u8bPL7y-W', 'class' => 'all', 'subject' => ''],
        ['title' => 'Phone clip', 'url' => 'https://youtu.be/07HMkj8A5VY', 'class' => '10', 'subject' => 'Science', 'tall' => true],
        ['title' => 'Not a YouTube link', 'url' => 'https://example.com/video.mp4', 'class' => 'all'],
    ]]]);

    $response = $this->get(route('tuition'))->assertOk()
        ->assertSeeInOrder(['id="sample-classes"', 'Watch a real class', 'id="fees"'], false)
        ->assertSee('data-youtube="mPaxGO480bY"', false)
        ->assertSee('data-youtube="Yzcf6-dT8ow"', false)
        ->assertSee('Class 6 · Maths')
        ->assertSee('All classes')
        ->assertSee('data-class="10"', false)
        ->assertSee('Book a free demo class')
        ->assertDontSee('<iframe', false)
        ->assertDontSee('Not a YouTube link');

    // Wide video spans two columns; the Short and the upright clip are tall.
    $html = $response->getContent();
    expect(substr_count($html, 'aspect-[9/16]'))->toBe(2)
        ->and($html)->toContain('col-span-2');
});

test('MKT-1 the admin adds a sample class with its class and subject', function () {
    $tuition = SiteSection::where('key', 'tuition')->sole();
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $tuition->getRouteKey()])
        ->fillForm(function (array $state) {
            $state['content']['samples'] = [['title' => 'Light and shadows', 'url' => 'https://youtube.com/shorts/Yzcf6-dT8ow', 'class' => '8', 'subject' => 'Science', 'tall' => false]];

            return $state;
        })->call('save')->assertHasNoFormErrors();

    expect($tuition->fresh()->content['samples'][0])
        ->url->toBe('https://youtube.com/shorts/Yzcf6-dT8ow')
        ->class->toEqual('8')
        ->subject->toBe('Science');
    $this->get(route('tuition'))->assertSee('data-youtube="Yzcf6-dT8ow"', false)->assertSee('Class 8 · Science');
});

test('MKT-1 the admin cannot save a sample class link that is not a YouTube video', function () {
    $tuition = SiteSection::where('key', 'tuition')->sole();
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $tuition->getRouteKey()])
        ->fillForm(function (array $state) {
            $state['content']['samples'] = [['title' => 'Bad link', 'url' => 'https://example.com/video.mp4', 'class' => 'all', 'subject' => '', 'tall' => false]];

            return $state;
        })->call('save')->assertHasFormErrors(['content.samples.0.url']);

    expect($tuition->fresh()->content['samples'] ?? [])->toBe([]);
});

test('only youtube.com/shorts links count as Shorts', function (string $link, bool $short) {
    expect(YouTube::isShort($link))->toBe($short);
})->with([
    ['https://youtube.com/shorts/Yzcf6-dT8ow?si=abc', true],
    ['https://www.youtube.com/shorts/Yzcf6-dT8ow', true],
    ['https://youtu.be/mPaxGO480bY', false],
    ['https://evil.example/shorts/Yzcf6-dT8ow', false],
]);
