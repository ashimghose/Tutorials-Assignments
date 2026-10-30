<?php

use App\Enums\LeadStatus;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Filament\Widgets\LeadSources;
use App\Filament\Widgets\LeadsPerWeekChart;
use App\Filament\Widgets\LeadStats;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\SiteSection;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('CMS-1 the admin edits a page and the change is live and audited', function () {
    $home = SiteSection::where('key', 'home')->sole();

    Livewire::test(EditSiteSection::class, ['record' => $home->getRouteKey()])
        ->fillForm(fn (array $state) => data_set($state, 'content.hero.title', 'Learn better with us'))
        ->call('save')
        ->assertHasNoFormErrors();

    expect($home->fresh()->content['hero']['title'])->toBe('Learn better with us')
        ->and($home->fresh()->content['hero']['lead'])->toBe($home->content['hero']['lead']);
    $this->get(route('home'))->assertSee('Learn better with us');

    $log = AuditLog::where('subject_id', $home->id)->latest('id')->first();
    expect($log?->user_id)->toBe($this->admin->id);
});

test('CMS-1 the admin edits headings and buttons on a section page', function () {
    $tuition = SiteSection::where('key', 'tuition')->sole();

    Livewire::test(EditSiteSection::class, ['record' => $tuition->getRouteKey()])
        ->fillForm(fn (array $state) => data_set($state, 'content.text.fees_title', 'Fair fees for every family'))
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get(route('tuition'))->assertSee('Fair fees for every family')->assertDontSee('Simple monthly fees');
});

test('CMS-1 the admin edits the about, policy and contact pages', function (string $key, string $route) {
    $section = SiteSection::where('key', $key)->sole();
    $field = $key === 'contact' ? 'content.cards.tuition.title' : 'content.body';
    $value = $key === 'contact' ? 'Tuition for your child' : "## Our story\n\nStarted by a teacher in Delhi.";

    Livewire::test(EditSiteSection::class, ['record' => $section->getRouteKey()])
        ->fillForm(fn (array $state) => data_set($state, $field, $value))
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get(route($route))->assertOk()->assertSee($key === 'contact' ? 'Tuition for your child' : 'Started by a teacher in Delhi.');
})->with([
    'about' => ['about', 'about'],
    'privacy' => ['privacy', 'privacy'],
    'terms' => ['terms', 'terms'],
    'refunds' => ['refunds', 'refunds'],
    'contact' => ['contact', 'contact'],
]);

test('CMS-1 page text strips HTML so a pasted script can\'t run', function () {
    SiteSection::where('key', 'about')->sole()->update(['content' => ['title' => 'About us', 'lead' => '', 'description' => 'About', 'body' => 'Hello <script>alert(1)</script>']]);

    $this->get(route('about'))->assertSee('Hello')->assertDontSee('<script>alert(1)</script>', false);
});

test('CMS-1 academic prices round-trip between rupees in the form and paise in storage', function () {
    // Before online orders (M6) the price list is edited with the page.
    config(['features.academic_orders' => false]);
    Repeater::fake();
    $academic = SiteSection::where('key', 'academic')->sole();
    $before = $academic->content['services'];

    $page = Livewire::test(EditSiteSection::class, ['record' => $academic->getRouteKey()])
        ->assertSchemaStateSet(['content.services.0.price_phd' => 4000]);

    $page->fillForm(fn (array $state) => data_set($state, 'content.services.0.price_phd', 4500))->call('save')->assertHasNoFormErrors();

    $services = $academic->fresh()->content['services'];
    expect($services[0]['prices'])->toBe([$before[0]['prices'][0], $before[0]['prices'][1], 450000])
        ->and($services[1]['prices'])->toBe($before[1]['prices'])
        ->and($services[0])->not->toHaveKey('price_phd');
});

test('CMS-2 the admin publishes an article', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'How to pick a thesis topic',
            'slug' => 'pick-a-thesis-topic',
            'offering' => 'academic',
            'description' => 'Five questions to ask before you start.',
            'body' => "## Start early\n\nTalk to your guide.",
            'published_at' => now()->subMinute(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::where('slug', 'pick-a-thesis-topic')->exists())->toBeTrue();
    $this->get(route('resources.show', 'pick-a-thesis-topic'))->assertOk()->assertSee('Talk to your guide.');
});

test('MKT-5 the admin marks a lead as converted, and it is audited', function () {
    $lead = Lead::factory()->create();

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$lead])
        ->callTableAction('mark_converted', $lead);

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted)
        ->and($lead->fresh()->became_customer_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'lead.status')->where('subject_id', $lead->id)->exists())->toBeTrue();
});

test('CMS-3 the marketing list holds only people who agreed, and the download is audited', function () {
    Lead::factory()->create(['name' => 'Agreed Person', 'marketing_consent' => true]);
    Lead::factory()->create(['name' => 'Not Agreed', 'marketing_consent' => false]);

    Livewire::test(ListLeads::class)
        ->callAction('exportMarketingList')
        ->assertFileDownloaded(contentType: 'text/csv');

    expect(AuditLog::where('action', 'leads.marketing_export')->exists())->toBeTrue();
});

test('MKT-5 the lead widgets render', function () {
    Lead::factory()->count(2)->create(['utm_source' => 'instagram', 'offering' => 'tuition']);
    Lead::factory()->create(['utm_source' => 'instagram', 'offering' => 'tuition', 'status' => LeadStatus::Converted]);
    Lead::factory()->create(['utm_source' => null, 'offering' => 'academic']);

    Livewire::test(LeadStats::class)->assertOk();
    Livewire::test(LeadsPerWeekChart::class)->assertOk();
    Livewire::test(LeadSources::class)->assertOk()->assertSeeInOrder(['instagram', '3', '1'])->assertSee('unknown');
});

test('non-admins cannot reach the content or lead screens', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/admin/site-sections')->assertForbidden();
    $this->get('/admin/leads')->assertForbidden();
    $this->get('/admin/articles')->assertForbidden();
})->with(['parent', 'academicStudent', 'teacher']);
