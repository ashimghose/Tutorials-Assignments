<?php

use App\Enums\LeadKind;
use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Notifications\NewLead;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(ContentSeeder::class);
    config(['features.cms' => true]);
});

function leadForm(array $overrides = []): array
{
    return [
        'name' => 'Asha Verma',
        'mobile' => '98765 43210',
        'email' => 'asha@example.com',
        'class_or_service' => 'Class 8, CBSE, Maths',
        'interest' => 'Free demo class',
        'section' => 'tuition',
        'page' => 'tuition',
        'price_range' => 'Up to ₹1,500 a month per subject',
        'marketing_consent' => '1',
        ...$overrides,
    ];
}

test('the site forms are hidden while FEATURE_CMS is off', function () {
    config(['features.cms' => false]);

    $this->get(route('enquire.show', 'tuition'))->assertNotFound();
    $this->post(route('enquire.store', 'tuition'), leadForm())->assertNotFound();
});

test('CMS-3 the section pages link to the site\'s own form instead of Google Forms', function () {
    $this->get(route('tuition'))
        ->assertSee(e(route('enquire.show', ['form' => 'tuition', 'section' => 'tuition', 'page' => 'tuition', 'interest' => 'Free demo class'])), false)
        ->assertDontSee('docs.google.com');

    $this->get(route('enquire.show', ['form' => 'tuition', 'interest' => 'Free demo class']))
        ->assertOk()
        ->assertSee('Book a free demo class')
        ->assertSee('Which monthly fee suits you?');
});

test('CMS-3 MKT-3 a demo booking is stored with the campaign the visitor arrived from', function () {
    $this->get(route('tuition', ['utm_source' => 'whatsapp', 'utm_medium' => 'status', 'utm_campaign' => 'oct-demo']));
    $this->get(route('about'));

    $this->post(route('enquire.store', 'tuition'), leadForm())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('enquire.thanks', ['section' => 'tuition']));

    $lead = Lead::sole();
    expect($lead->kind)->toBe(LeadKind::Demo)
        ->and($lead->offering)->toBe('tuition')
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->mobile)->toBe('9876543210')
        ->and($lead->utm_source)->toBe('whatsapp')
        ->and($lead->utm_medium)->toBe('status')
        ->and($lead->utm_campaign)->toBe('oct-demo')
        ->and($lead->page)->toBe('tuition')
        ->and($lead->marketing_consent)->toBeTrue();
});

test('MKT-3 without tags, the referring site is recorded as the source', function () {
    $this->get(route('academic'), ['referer' => 'https://www.google.com/search?q=thesis']);

    $this->post(route('enquire.store', 'academic'), leadForm(['interest' => 'General enquiry', 'section' => 'academic', 'price_range' => null]));

    expect(Lead::sole()->utm_source)->toBe('www.google.com')
        ->and(Lead::sole()->kind)->toBe(LeadKind::Enquiry);
});

test('MKT-4 a waitlist sign-up from the academic section is an academic waitlist lead', function () {
    $this->post(route('enquire.store', 'waitlist'), leadForm(['section' => 'academic', 'interest' => 'Academic: printing in my city', 'price_range' => null]));

    expect(Lead::sole()->kind)->toBe(LeadKind::Waitlist)->and(Lead::sole()->offering)->toBe('academic');
});

test('a lead needs a name and an Indian mobile, and the price must be one of the choices', function () {
    $this->post(route('enquire.store', 'tuition'), leadForm(['name' => '', 'mobile' => '12345', 'price_range' => '₹1']))
        ->assertSessionHasErrors(['name', 'mobile', 'price_range']);

    expect(Lead::count())->toBe(0);
});

test('a filled-in honeypot is refused', function () {
    $this->post(route('enquire.store', 'tuition'), leadForm(['website' => 'http://spam.example']))
        ->assertSessionHasErrors('website');

    expect(Lead::count())->toBe(0);
});

test('the business is emailed about each new lead when a contact email is set', function () {
    Notification::fake();
    config(['site.contact.email' => 'owner@example.com']);

    $this->post(route('enquire.store', 'tuition'), leadForm());

    Notification::assertSentOnDemand(NewLead::class, fn (NewLead $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'owner@example.com');
});

test('lead forms are rate limited to 5 a minute', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('enquire.store', 'tuition'), leadForm(['name' => '']));
    }

    $this->post(route('enquire.store', 'tuition'), leadForm())->assertTooManyRequests();
});

test('MKT-2 a campaign page collects leads tagged with the campaign', function () {
    $campaign = Campaign::create([
        'slug' => 'phd-synopsis-oct', 'offering' => 'academic', 'title' => 'PhD synopsis review',
        'headline' => 'Get your PhD synopsis reviewed', 'offer' => 'Free 15-minute structure check', 'interest' => 'Campaign: PhD synopsis',
    ]);

    $this->get(route('campaign.show', $campaign))->assertOk()->assertSee('Get your PhD synopsis reviewed')->assertSee('Free 15-minute structure check');

    $this->post(route('campaign.store', $campaign), leadForm(['interest' => 'tampered', 'price_range' => null]))
        ->assertRedirect(route('enquire.thanks', ['section' => 'academic']));

    $lead = Lead::sole();
    expect($lead->campaign_id)->toBe($campaign->id)
        ->and($lead->offering)->toBe('academic')
        ->and($lead->interest)->toBe('Campaign: PhD synopsis')
        ->and($lead->utm_campaign)->toBe('phd-synopsis-oct')
        ->and($lead->page)->toBe('campaign:phd-synopsis-oct');
});

test('MKT-2 a campaign page that is switched off is not found', function () {
    $campaign = Campaign::create(['slug' => 'old', 'offering' => 'tuition', 'title' => 'Old', 'headline' => 'Old', 'is_active' => false]);

    $this->get(route('campaign.show', $campaign))->assertNotFound();
    $this->post(route('campaign.store', $campaign), leadForm())->assertNotFound();
});
