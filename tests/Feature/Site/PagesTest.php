<?php

use App\Support\SiteContent;

dataset('public pages', [
    'home' => ['/', 'Learning support for school and beyond'],
    'tuition' => ['/tuition', 'Live online tuition for classes 1 to 12'],
    'academic' => ['/academic', 'Guidance, editing and printing'],
    'resources' => ['/resources', 'Free guides'],
    'article' => ['/resources/how-to-structure-a-synopsis', 'How to structure a research synopsis'],
    'about' => ['/about', 'About us'],
    'contact' => ['/contact', 'Contact us'],
    'privacy' => ['/privacy', 'Privacy policy'],
    'terms' => ['/terms', 'Terms of use'],
    'refunds' => ['/refund-policy', 'Refund policy'],
]);

test('CMS-2 CMS-4 public pages load with a title, description and canonical link', function (string $path, string $heading) {
    $this->get($path)
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('<meta name="description"', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('<meta property="og:title"', false);
})->with('public pages');

test('public pages stay exportable as static HTML: no forms or CSRF tokens', function (string $path) {
    $this->get($path)
        ->assertDontSee('<form', false)
        ->assertDontSee('name="_token"', false)
        ->assertDontSee('csrf-token', false);
})->with('public pages');

test('MKT-1 tuition and academic sections show their prices and FAQs', function () {
    config(['site.forms.tuition.url' => null, 'site.forms.academic.url' => null]);

    $this->get('/tuition')
        ->assertSee('₹1,200')
        ->assertSee('Questions parents ask')
        ->assertSee('Book a free demo class')
        ->assertSee('"@type":"FAQPage"', false);

    $this->get('/academic')
        ->assertSee('₹15,000')
        ->assertSee('Hard bound copy')
        ->assertSee('Questions students ask')
        ->assertSee('Request a quote');
});

test('CHAT-6 the WhatsApp button carries the message for the section', function () {
    config([
        'site.whatsapp.number' => '919876543210',
        'site.whatsapp.text.tuition' => 'Tuition hello',
        'site.whatsapp.text.academic' => 'Academic hello',
        'site.whatsapp.text.general' => 'General hello',
    ]);

    $this->get('/tuition')->assertSee('wa.me/919876543210?text=Tuition%20hello', false);
    $this->get('/academic')->assertSee('wa.me/919876543210?text=Academic%20hello', false);
    $this->get('/')->assertSee('wa.me/919876543210?text=General%20hello', false);
    $this->get('/resources/how-to-structure-a-synopsis')->assertSee('text=Academic%20hello', false);
    $this->get('/resources/board-exam-90-day-plan')->assertSee('text=Tuition%20hello', false);
});

test('MKT-3 MKT-4 enquiry and waitlist buttons link to their Google Forms with campaign entries', function () {
    config([
        'site.forms.tuition.url' => 'https://docs.google.com/forms/d/e/tuition/viewform',
        'site.forms.tuition.entries' => ['source' => 'entry.11', 'medium' => null, 'campaign' => 'entry.13', 'page' => 'entry.14', 'interest' => 'entry.15'],
        'site.forms.waitlist.url' => 'https://docs.google.com/forms/d/e/waitlist/viewform',
        'site.forms.waitlist.entries' => ['source' => null, 'medium' => null, 'campaign' => null, 'page' => null, 'interest' => 'entry.25'],
    ]);

    $this->get('/tuition')
        ->assertSee('https://docs.google.com/forms/d/e/tuition/viewform?usp=pp_url&amp;entry.14=tuition&amp;entry.15=Free%20demo%20class', false)
        ->assertSee('data-utm-entries="{&quot;source&quot;:&quot;entry.11&quot;,&quot;campaign&quot;:&quot;entry.13&quot;}"', false)
        ->assertSee('https://docs.google.com/forms/d/e/waitlist/viewform?usp=pp_url&amp;entry.25=Tuition%3A%20new%20batch', false);
});

test('Cloudflare Web Analytics loads only when its token is set', function () {
    config(['site.analytics_token' => null]);
    $this->get('/')->assertDontSee('cloudflareinsights', false);

    config(['site.analytics_token' => 'abc123']);
    $this->get('/')
        ->assertSee('static.cloudflareinsights.com/beacon.min.js', false)
        ->assertSee('abc123', false);
});

test('ACA academic copy never offers finished work to submit as the student\'s own', function () {
    $html = strtolower($this->get('/academic')->getContent());

    foreach (['we write your', 'write your thesis for you', 'ready-made thesis', 'ghostwrit', 'buy a thesis', 'guaranteed plagiarism-free'] as $phrase) {
        expect($html)->not->toContain($phrase);
    }

    expect($html)->toContain('we don\'t do it for you');
});

test('ACA-3 the academic section sends under-18s to tuition', function () {
    $this->get('/academic')
        ->assertSee('aged 18 or older')
        ->assertSee(route('tuition'), false);
});

test('MKT-6 every listed guide has a page and unknown guides are 404', function () {
    foreach (SiteContent::articles() as $article) {
        $this->get(route('resources.show', $article['slug']))->assertOk()->assertSee($article['title']);
    }

    $this->get('/resources/not-a-guide')->assertNotFound();
});

test('CMS-4 the sitemap lists every public page and guide', function () {
    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    foreach (['/tuition', '/academic', '/resources', '/privacy', '/terms', '/resources/how-to-structure-a-synopsis'] as $path) {
        $response->assertSee('<loc>'.url($path).'</loc>', false);
    }
});
