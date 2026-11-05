<?php

use App\Support\Phone;

test('a mobile is stored in E.164 form for the country picked', function (string $number, string $country, string $e164) {
    expect(Phone::e164($number, $country))->toBe($e164);
})->with([
    'India, spaces' => ['98765 43210', 'IN', '+919876543210'],
    'India, with +91' => ['+91 98765 43210', 'IN', '+919876543210'],
    'India, with 0' => ['09876543210', 'IN', '+919876543210'],
    'UAE' => ['050 123 4567', 'AE', '+971501234567'],
    'Bangladesh' => ['01712 345678', 'BD', '+8801712345678'],
    'typed with its own country code' => ['+971 50 123 4567', 'IN', '+971501234567'],
]);

test('a number that is not a valid mobile for its country is refused', function (string $number, string $country) {
    expect(Phone::isValid($number, $country))->toBeFalse()
        ->and(Phone::e164($number, $country))->toBeNull();
})->with([
    'too short' => ['12345', 'IN'],
    'an Indian landline' => ['011 2345 6789', 'IN'],
    'an Indian mobile under the UAE' => ['98765 43210', 'AE'],
    'empty' => ['', 'IN'],
    'letters' => ['call me', 'IN'],
]);

test('an unknown country falls back to India', function () {
    expect(Phone::country('xx'))->toBe('IN')
        ->and(Phone::country('ae'))->toBe('AE')
        ->and(Phone::e164('98765 43210', 'XX'))->toBe('+919876543210');
});

test('stored numbers are shown, linked and split for people', function () {
    expect(Phone::display('+919876543210'))->toBe('+91 98765 43210')
        ->and(Phone::display('9876543210'))->toBe('+91 98765 43210')
        ->and(Phone::whatsappUrl('+971501234567'))->toBe('https://wa.me/971501234567')
        ->and(Phone::whatsappUrl('9876543210'))->toBe('https://wa.me/919876543210')
        ->and(Phone::split('+971501234567'))->toBe(['country' => 'AE', 'number' => '50 123 4567'])
        ->and(Phone::split(null))->toBe(['country' => 'IN', 'number' => '']);
});

test('the dropdown shows India, UAE and Bangladesh first, with flags and dial codes', function () {
    expect(Phone::PREFERRED_COUNTRIES)->toBe(['IN', 'AE', 'BD'])
        ->and(Phone::countries())->toContain('IN', 'AE', 'BD')
        ->and(Phone::dialCode('BD'))->toBe('+880')
        ->and(Phone::flag('IN'))->toBe('🇮🇳');
});
