<?php

use App\Rules\MobileNumber;
use App\Support\ClientRules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * The browser shows the server's own messages: each data-msg-* matches what
 * the validator says for the same rule.
 */
function serverMessage(string $field, mixed $value, array $rules, array $messages = []): string
{
    return Validator::make([$field => $value], [$field => $rules], $messages)->errors()->first($field);
}

test('required, length and email rules become browser attributes with the server messages', function () {
    $rules = ['required', 'string', 'email', 'max:20'];
    $attributes = ClientRules::attributes('email', $rules);

    expect($attributes['required'])->toBeTrue()
        ->and($attributes['maxlength'])->toBe(20)
        ->and($attributes['data-msg-required'])->toBe(serverMessage('email', '', $rules))
        ->and($attributes['data-msg-maxlength'])->toBe(serverMessage('email', str_repeat('a', 10).'@example.com', $rules))
        ->and($attributes['data-msg-email'])->toBe(serverMessage('email', 'not-an-email', $rules));
});

test('custom messages, numbers, digits and confirmation match the server', function () {
    $messages = ['consent.accepted' => 'Please agree.'];

    expect(ClientRules::attributes('consent', ['accepted'], $messages)['data-msg-required'])->toBe(serverMessage('consent', null, ['accepted'], $messages));

    $pages = ClientRules::attributes('pages', ['nullable', 'integer', 'min:1', 'max:5000']);
    expect($pages)->toMatchArray(['min' => 1, 'max' => 5000])
        ->and($pages['data-msg-max'])->toBe(serverMessage('pages', 6000, ['nullable', 'integer', 'min:1', 'max:5000']));

    $pin = ClientRules::attributes('delivery_pincode', ['nullable', 'digits:6']);
    expect($pin['data-pattern'])->toBe('^\d{6}$')
        ->and($pin['data-msg-pattern'])->toBe(serverMessage('delivery_pincode', '123', ['nullable', 'digits:6']));

    $password = ClientRules::attributes('password', ['required', 'string', Password::min(12)->mixedCase()->numbers(), 'confirmed']);
    expect($password)->toMatchArray(['minlength' => 12, 'data-password' => 'mixed numbers', 'data-confirmed' => 'password_confirmation'])
        ->and($password['data-msg-confirmed'])->toBe(serverMessage('password', 'a', ['confirmed']));
});

test('a mobile number field is checked against its own country field', function () {
    $attributes = ClientRules::attributes('delivery_phone', ['nullable', 'string', 'max:30', new MobileNumber('delivery_phone_country')]);

    expect($attributes['data-phone'])->toBe('delivery_phone_country')
        ->and($attributes['data-msg-phone'])->toBe(serverMessage('delivery_phone', '123', [new MobileNumber('delivery_phone_country')]));
});
