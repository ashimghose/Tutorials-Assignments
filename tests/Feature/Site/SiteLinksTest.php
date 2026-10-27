<?php

use App\Support\SiteLinks;

test('CHAT-6 each section has its own pre-filled WhatsApp message', function () {
    config([
        'site.whatsapp.number' => '+91 98765 43210',
        'site.whatsapp.numbers' => [],
        'site.whatsapp.text.tuition' => "Hi, I'm asking about tuition",
        'site.whatsapp.text.academic' => "Hi, I'm asking about academic services",
    ]);

    expect(SiteLinks::whatsapp('tuition'))
        ->toBe('https://wa.me/919876543210?text=Hi%2C%20I%27m%20asking%20about%20tuition')
        ->and(SiteLinks::whatsapp('academic'))
        ->toContain('academic%20services');
});

test('CHAT-6 tuition and academic chats go to their own numbers, the rest to the main number', function () {
    config([
        'site.whatsapp.number' => '919000000001',
        'site.whatsapp.numbers.tuition' => '+91 98887 27082',
        'site.whatsapp.numbers.academic' => '919958067504',
    ]);

    expect(SiteLinks::whatsapp('tuition'))->toStartWith('https://wa.me/919888727082?')
        ->and(SiteLinks::whatsapp('academic'))->toStartWith('https://wa.me/919958067504?')
        ->and(SiteLinks::whatsapp('general'))->toStartWith('https://wa.me/919000000001?');
});

test('CHAT-6 a section without its own number falls back to the main number', function () {
    config([
        'site.whatsapp.number' => '919000000001',
        'site.whatsapp.numbers.tuition' => null,
        'site.whatsapp.numbers.academic' => '',
    ]);

    expect(SiteLinks::whatsapp('tuition'))->toStartWith('https://wa.me/919000000001?')
        ->and(SiteLinks::whatsapp('academic'))->toStartWith('https://wa.me/919000000001?');
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
        ->toBe(['href' => 'https://docs.google.com/forms/d/e/xyz/viewform', 'is_form' => true, 'external' => true, 'utm_entries' => []]);
});

test('form links fall back to the section WhatsApp chat until the form URL is set', function () {
    config(['site.forms.waitlist.url' => null, 'site.whatsapp.number' => '919876543210', 'site.whatsapp.numbers' => []]);

    $link = SiteLinks::form('waitlist', 'tuition', 'tuition', 'New batch');

    expect($link['is_form'])->toBeFalse()
        ->and($link['href'])->toBe(SiteLinks::whatsapp('tuition'));
});
