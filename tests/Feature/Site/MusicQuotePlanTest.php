<?php

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Models\SiteSection;
use App\Models\User;
use App\Support\SiteContent;
use Database\Seeders\ContentSeeder;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function () {
    config(['verticals.music.enabled' => true, 'site.forms.music.url' => null]);
});

function oneToOneMigration(): object
{
    return require database_path('migrations/2026_11_23_000001_add_one_to_one_music_plan.php');
}

test('MUS-1 the group fee says one class a week and the one-to-one card asks for a quote', function () {
    $this->get('/music')->assertOk()
        ->assertSeeInOrder(['Guitar group classes', '₹1,000', 'per learner, per month, for one class a week', 'Book a free trial'])
        ->assertSeeInOrder(['Dedicated (one-to-one) class', 'Fee on request', 'One or more classes a week', 'Get a quote', 'or ask on WhatsApp'])
        ->assertSee('data-plan="quote"', false)
        ->assertSee('sm:grid-cols-2', false);
});

test('MUS-1 the live music page gets the one-to-one card once and the new group wording', function () {
    config(['features.cms' => true]);
    $migration = oneToOneMigration();

    $music = SiteContent::sectionFile('music');
    foreach ($migration::TEXT as $key => [$old]) {
        $music['text'][$key] = $old;
    }
    $music['plans'] = [[
        'name' => 'Guitar group classes', 'price' => 100000, 'period' => $migration::GROUP_PERIOD[0],
        'subjects' => ['Acoustic guitar'], 'features' => $migration::GROUP_FEATURES[0],
    ]];
    $row = SiteSection::create(['key' => 'music', 'content' => $music]);

    $migration->up();
    $migration->up();

    $content = $row->fresh()->content;
    expect($content['text']['fees_title'])->toBe('Group or one-to-one classes')
        ->and($content['plans'])->toHaveCount(2)
        ->and($content['plans'][0]['period'])->toBe($migration::GROUP_PERIOD[1])
        ->and($content['plans'][0]['features'])->toBe($migration::GROUP_FEATURES[1])
        ->and($content['plans'][1]['name'])->toBe('Dedicated (one-to-one) class')
        ->and($content['plans'][1]['price'])->toBeNull();

    $this->get('/music')->assertOk()->assertSee('Fee on request')->assertSee('Get a quote');
});

test('MUS-1 music fee wording the admin has changed is left alone', function () {
    config(['features.cms' => true]);
    $migration = oneToOneMigration();

    $music = SiteContent::sectionFile('music');
    $music['text']['fees_title'] = 'Our fees';
    $music['plans'] = [['name' => 'Guitar group classes', 'price' => 120000, 'period' => 'per month, weekly class', 'subjects' => [], 'features' => ['Saturdays']]];
    $row = SiteSection::create(['key' => 'music', 'content' => $music]);

    $migration->up();

    $content = $row->fresh()->content;
    expect($content['text']['fees_title'])->toBe('Our fees')
        ->and($content['plans'][0]['period'])->toBe('per month, weekly class')
        ->and($content['plans'][0]['features'])->toBe(['Saturdays'])
        ->and($content['plans'])->toHaveCount(2);
});

test('MUS-1 the admin edits both fee cards and can leave a fee empty for a quote', function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
    $music = SiteSection::where('key', 'music')->sole();
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $music->getRouteKey()])
        ->fillForm(function (array $state) {
            $plans = array_values($state['content']['plans']);
            $plans[0] = [...$plans[0], 'price' => 1200, 'period' => 'per month, one class every Saturday'];
            $plans[1] = [...$plans[1], 'name' => 'Private lessons', 'price' => null];
            $state['content']['plans'] = $plans;
            $state['content']['text']['quote_button'] = 'Ask for a price';

            return $state;
        })->call('save')->assertHasNoFormErrors();

    $plans = $music->fresh()->content['plans'];
    expect($plans[0]['price'])->toBe(120000)
        ->and($plans[1]['price'])->toBeNull();

    $this->get('/music')->assertOk()
        ->assertSeeInOrder(['₹1,200', 'per month, one class every Saturday'])
        ->assertSeeInOrder(['Private lessons', 'Fee on request', 'Ask for a price']);
});

test('MUS-1 a broken fee plan does not break the music page', function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
    $music = SiteSection::where('key', 'music')->sole();
    $content = $music->content;
    $content['plans'][] = 'not a plan';
    $content['plans'][] = ['name' => 'Half filled'];
    $music->update(['content' => $content]);

    $this->get('/music')->assertOk()->assertSee('Guitar group classes')->assertSee('Half filled');
});
