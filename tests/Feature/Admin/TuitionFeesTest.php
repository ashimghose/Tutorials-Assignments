<?php

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Models\SiteSection;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function () {
    config(['features.cms' => true, 'site.forms.tuition.url' => null]);
    $this->seed(ContentSeeder::class);
    $this->tuition = SiteSection::where('key', 'tuition')->sole();
});

/**
 * The tuition page as the live site saved it before the classroom fee existed:
 * one price per plan and no "Online" / "Physical classroom" labels.
 */
function oldTuitionContent(SiteSection $tuition): void
{
    $content = $tuition->content;
    $content['plans'] = array_map(fn (array $plan) => array_diff_key($plan, ['classroom_price' => true]), $content['plans']);
    unset($content['text']['online_label'], $content['text']['classroom_label']);
    $tuition->update(['content' => $content]);
}

test('MKT-1 each tuition plan shows its online and physical classroom fee', function () {
    $this->get(route('tuition'))->assertOk()
        ->assertSeeInOrder(['Classes 1 to 7', 'Online', '₹700', 'Physical classroom', '₹1,000'])
        ->assertSeeInOrder(['Classes 8 to 10', '₹1,000', '₹1,500'])
        ->assertSeeInOrder(['Classes 11 and 12', '₹1,500', '₹2,000'])
        ->assertSee('lg:grid-cols-3', false);
});

test('MKT-1 a plan without a classroom fee shows one fee, even on a page saved before the labels existed', function () {
    oldTuitionContent($this->tuition);

    $this->get(route('tuition'))->assertOk()
        ->assertSee('₹700')
        ->assertDontSee('Physical classroom');
});

test('MKT-1 a half-filled or broken plan does not break the tuition page', function () {
    $content = $this->tuition->content;
    $content['plans'][] = ['name' => 'Classes 13'];
    $content['plans'][] = 'not a plan';
    $content['plans'][0]['features'] = null;
    unset($content['plans'][1]['subjects']);
    $this->tuition->update(['content' => $content]);

    $this->get(route('tuition'))->assertOk()
        ->assertSee('Classes 1 to 7')
        ->assertDontSee('Classes 13');
});

test('MKT-1 the admin changes the fee bands and classroom fees on a page saved before the classroom fee existed', function () {
    oldTuitionContent($this->tuition);
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    $page = Livewire::test(EditSiteSection::class, ['record' => $this->tuition->getRouteKey()])
        ->assertSchemaStateSet(['content.text.classroom_label' => 'Physical classroom']);

    $page->fillForm(function (array $state) {
        $plans = array_values($state['content']['plans']);
        $plans[0] = [...$plans[0], 'name' => 'Classes 1 to 4', 'price' => 600, 'classroom_price' => 900];
        $plans[1]['classroom_price'] = null;
        $state['content']['plans'] = array_slice($plans, 0, 2);

        return $state;
    })->call('save')->assertHasNoFormErrors();

    $plans = $this->tuition->fresh()->content['plans'];
    expect($plans)->toHaveCount(2)
        ->and($plans[0]['price'])->toBe(60000)
        ->and($plans[0]['classroom_price'])->toBe(90000)
        ->and($plans[1]['classroom_price'])->toBeNull();

    $this->get(route('tuition'))->assertOk()
        ->assertSeeInOrder(['Classes 1 to 4', 'Online', '₹600', 'Physical classroom', '₹900'])
        ->assertSee('sm:grid-cols-2', false)
        ->assertDontSee('Classes 11 and 12');
});

test('MKT-1 only admins can change the fees', function () {
    $this->actingAs(User::factory()->create());

    $this->get("/admin/site-sections/{$this->tuition->getRouteKey()}/edit")->assertForbidden();
});
