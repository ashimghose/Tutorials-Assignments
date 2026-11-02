<?php

use App\Enums\Offering;
use App\Models\Lead;
use App\Models\Teacher;
use App\Services\MoneyReport;
use App\Support\LeadForms;
use App\Support\Verticals;
use Carbon\CarbonImmutable;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Notification;

function musicOn(array $settings = []): void
{
    config(['verticals.music' => ['enabled' => true, 'domain' => null, 'brand' => ['name' => null, 'logo' => null], ...$settings]]);
}

test('VRT-1 music stays hidden until it is switched on', function () {
    $this->get('/music')->assertNotFound();
    $this->get('/')->assertDontSee(route('music'));
    $this->get('/sitemap.xml')->assertDontSee('<loc>'.url('/music').'</loc>', false);

    musicOn();

    $this->get('/music')->assertOk()
        ->assertSee('Live online music lessons: singing and instruments')
        ->assertSee('/images/illustrations/music-live-class.svg', false);
    $this->get('/')->assertSee('href="'.route('music').'"', false)->assertSee('Explore music lessons');
    $this->get('/sitemap.xml')->assertSee('<loc>'.url('/music').'</loc>', false);
});

test('VRT-1 a switched-off section disappears from the menu, home page and sitemap', function () {
    config(['verticals.tuition.enabled' => false]);

    $this->get('/tuition')->assertNotFound();
    $this->get('/')->assertDontSee('href="'.route('tuition').'"', false)->assertSee('href="'.route('academic').'"', false);
    $this->get('/sitemap.xml')->assertDontSee('<loc>'.url('/tuition').'</loc>', false);
});

test('VRT-2 the music page has its own WhatsApp message', function () {
    musicOn();
    config(['site.whatsapp.number' => '919000000001', 'site.whatsapp.numbers.music' => '919000000003']);

    $this->get('/music')->assertSee('https://wa.me/919000000003?text='.rawurlencode("Hi, I'm asking about music lessons"), false);
});

test('VRT-3 a section on its own domain gets 301 redirects from its old path, keeping UTM tags', function () {
    musicOn(['domain' => 'music.test']);

    $this->get('/music?utm_source=instagram&utm_campaign=diwali')
        ->assertStatus(301)
        ->assertRedirect('http://music.test/?utm_source=instagram&utm_campaign=diwali');
});

test('VRT-3 a section\'s own domain shows its landing page and sends the rest to the right place', function () {
    musicOn(['domain' => 'music.test']);

    $this->get('http://music.test/')->assertOk()->assertSee('Live online music lessons: singing and instruments');
    $this->get('http://music.test/music')->assertStatus(301)->assertRedirect('http://music.test/');
    $this->get('http://music.test/tuition')->assertStatus(301)->assertRedirect('http://localhost/tuition');
    $this->get('http://music.test/login')->assertRedirect('http://localhost/login');

    // Shared pages work there too, but point Google at the main domain.
    $this->get('http://music.test/about')->assertOk()->assertSee('<link rel="canonical" href="http://localhost/about">', false);
    $this->get('http://music.test/sitemap.xml')->assertOk()
        ->assertSee('<loc>http://music.test</loc>', false)
        ->assertDontSee('/about</loc>', false);

    // The main domain links to the music domain and leaves it out of its sitemap.
    $this->get('/')->assertSee('href="http://music.test/"', false);
    $this->get('/sitemap.xml')->assertDontSee('music', false);
});

test('VRT-3 a switched-off section\'s own domain is not found', function () {
    config(['verticals.music.domain' => 'music.test']);

    $this->get('http://music.test/')->assertNotFound();
});

test('VRT-5 a section can show its own brand name and logo', function () {
    musicOn(['brand' => ['name' => 'Sur Academy', 'logo' => '/images/brand/sur-academy.svg']]);

    $this->get('/music')
        ->assertSee('<title>Live online music lessons: singing and instruments | Sur Academy</title>', false)
        ->assertSee('<img src="/images/brand/sur-academy.svg" alt="Sur Academy"', false);

    // Other pages keep the main brand.
    $this->get('/tuition')->assertSee('<img src="/images/brand/indian-assignments-logo.svg" alt="Indian Assignments"', false);
});

test('VRT-2 VRT-4 a music enquiry is stored as a music lead, only while music is on', function () {
    $this->seed(ContentSeeder::class);
    config(['features.cms' => true]);
    Notification::fake();

    $form = [
        'name' => 'Ravi Das',
        'mobile' => '98765 43210',
        'class_or_service' => 'Guitar, beginner, age 12',
        'interest' => 'Free trial lesson',
        'section' => 'music',
        'page' => 'music',
    ];

    $this->get(route('enquire.show', ['form' => 'music']))->assertNotFound();
    $this->post(route('enquire.store', 'music'), $form)->assertNotFound();

    musicOn();

    $this->get(route('enquire.show', ['form' => 'music', 'interest' => 'Free trial lesson']))
        ->assertOk()
        ->assertSee('Book a free trial lesson')
        ->assertSee('Instrument or style, level and learner');
    $this->post(route('enquire.store', 'music'), $form)->assertRedirect(route('enquire.thanks', ['section' => 'music']));

    expect(Lead::sole()->offering)->toBe('music');
    $this->get(route('enquire.thanks', ['section' => 'music']))->assertOk()->assertSee('Back to music lessons');
});

test('VRT-1 a waitlist sign-up for music counts as tuition while music is off', function () {
    expect(LeadForms::offering('waitlist', 'music'))->toBe('tuition');

    musicOn();

    expect(LeadForms::offering('waitlist', 'music'))->toBe('music');
});

test('VRT-1 teachers can work in any mix of sections', function () {
    $tuition = Teacher::factory()->create(['offerings' => ['tuition']]);
    $both = Teacher::factory()->create(['offerings' => ['tuition', 'music']]);
    $academic = Teacher::factory()->create(['offerings' => ['academic']]);

    expect(Teacher::query()->worksInOffering(Offering::Music)->pluck('id')->all())->toBe([$both->id])
        ->and(Teacher::query()->worksInOffering(Offering::Tuition)->orderBy('id')->pluck('id')->all())->toBe([$tuition->id, $both->id])
        ->and($both->worksIn(Offering::Academic))->toBeFalse()
        ->and($both->offeringLabels())->toBe('Tuition, Music lessons')
        ->and($academic->worksIn(Offering::Academic))->toBeTrue();
});

test('VRT-4 the money report gets a music column once music is on', function () {
    $day = CarbonImmutable::parse('2026-11-02');

    expect(array_keys(app(MoneyReport::class)->between($day, $day)))->toBe(['tuition', 'academic', 'total']);

    musicOn();

    expect(array_keys(app(MoneyReport::class)->between($day, $day)))->toBe(['tuition', 'academic', 'music', 'total'])
        ->and(Offering::sectionOptions())->toBe(['tuition' => 'Tuition', 'academic' => 'Academic services', 'music' => 'Music lessons']);
});

test('VRT-3 a section is found from its domain or its path', function () {
    musicOn(['domain' => 'Music.Test']);

    expect(Verticals::forHost('music.test'))->toBe('music')
        ->and(Verticals::forHost('localhost'))->toBeNull()
        ->and(Verticals::url('music', 'fees'))->toBe('http://music.test/#fees')
        ->and(Verticals::url('tuition', 'fees'))->toBe(route('tuition').'#fees');
});
