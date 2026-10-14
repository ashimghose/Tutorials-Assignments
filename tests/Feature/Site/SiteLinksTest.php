<?php

use App\Support\SiteLinks;

test('CHAT-6 each section has its own pre-filled WhatsApp message', function () {
    config([
        'site.whatsapp.number' => '+91 98765 43210',
        'site.whatsapp.text.tuition' => "Hi, I'm asking about tuition",
        'site.whatsapp.text.academic' => "Hi, I'm asking about academic services",
    ]);

    expect(SiteLinks::whatsapp('tuition'))
        ->toBe('https://wa.me/919876543210?text=Hi%2C%20I%27m%20asking%20about%20tuition')
        ->and(SiteLinks::whatsapp('academic'))
        ->toContain('academic%20services');
});

test('MKT-3 form links are pre-filled with the page and interest, and expose the campaign entries', function () {
    config([
        'site.forms.academic.url' => 'https://docs.google.com/forms/d/e/abc/viewform?usp=sf_link',
        'site.forms.academic.entries' => [
            'source' => 'entry.1',
            'medium' => 'entry.2',
            'campaign' => 'entry.3',
            'page' => 'entry.4',
            'interest' => 'entry.5',
        ],
    ]);

    $link = SiteLinks::form('academic', 'academic', 'academic', 'Synopsis guidance');

    expect($link['is_form'])->toBeTrue()
        ->and($link['href'])->toBe('https://docs.google.com/forms/d/e/abc/viewform?usp=pp_url&entry.4=academic&entry.5=Synopsis%20guidance')
        ->and($link['utm_entries'])->toBe(['source' => 'entry.1', 'medium' => 'entry.2', 'campaign' => 'entry.3']);
});

test('form links skip entries that are not configured', function () {
    config([
        'site.forms.tuition.url' => 'https://docs.google.com/forms/d/e/xyz/viewform',
        'site.forms.tuition.entries' => ['source' => null, 'medium' => null, 'campaign' => null, 'page' => null, 'interest' => null],
    ]);

    expect(SiteLinks::form('tuition', 'tuition', 'tuition', 'Free demo class'))
        ->toBe(['href' => 'https://docs.google.com/forms/d/e/xyz/viewform', 'is_form' => true, 'utm_entries' => []]);
});

test('form links fall back to the section WhatsApp chat until the form URL is set', function () {
    config(['site.forms.waitlist.url' => null, 'site.whatsapp.number' => '919876543210']);

    $link = SiteLinks::form('waitlist', 'tuition', 'tuition', 'New batch');

    expect($link['is_form'])->toBeFalse()
        ->and($link['href'])->toBe(SiteLinks::whatsapp('tuition'));
});
