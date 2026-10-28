<?php

use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Models\User;

function parentSignUp(array $overrides = []): array
{
    return [
        'account_type' => 'parent',
        'name' => 'Asha Verma',
        'email' => 'asha@example.com',
        'mobile' => '+91 98765 43210',
        'password' => 'password',
        'password_confirmation' => 'password',
        'consent' => '1',
        ...$overrides,
    ];
}

test('the parent and academic sign-up screens render', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create a parent account');
    $this->get(route('register', ['as' => 'academic']))->assertOk()->assertSee('I am 18 or older');
});

test('AUTH-1 a parent signs up with mobile and consent, then must verify their email', function () {
    $this->post(route('register.store'), parentSignUp())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'asha@example.com')->sole();

    expect($user->role)->toBe(Role::Parent)
        ->and($user->parentProfile->mobile)->toBe('9876543210')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $consent = $user->consents()->sole();
    expect($consent->purpose)->toBe(ConsentPurpose::ParentAccount)
        ->and($consent->policy_version)->toBe(config('privacy.policy_version'))
        ->and($consent->given_at)->not->toBeNull();

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('AUTH-1 sign-up needs consent and a valid Indian mobile number', function () {
    $this->post(route('register.store'), parentSignUp(['consent' => null, 'mobile' => '12345']))
        ->assertSessionHasErrors(['consent', 'mobile']);

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

test('the role cannot be chosen through the sign-up form', function () {
    $this->post(route('register.store'), parentSignUp(['role' => 'admin']));

    expect(User::sole()->role)->toBe(Role::Parent);
});

test('ACA-3 an academic student signs up with the 18+ declaration', function () {
    $this->post(route('register.store'), parentSignUp([
        'account_type' => 'academic',
        'email' => 'ravi@example.com',
        'adult' => '1',
    ]))->assertSessionHasNoErrors();

    $user = User::where('email', 'ravi@example.com')->sole();

    expect($user->role)->toBe(Role::AcademicStudent)
        ->and($user->academicStudent->adult_declared_at)->not->toBeNull()
        ->and($user->consents()->sole()->purpose)->toBe(ConsentPurpose::AcademicSelf);
});

test('ACA-3 an academic student who does not declare 18+ is pointed to Tuition', function () {
    $this->post(route('register.store'), parentSignUp(['account_type' => 'academic', 'adult' => null]))
        ->assertSessionHasErrors(['adult' => 'Academic services are for students aged 18 or over. School students join through a parent on our Tuition page.']);

    expect(User::count())->toBe(0);
    $this->get(route('register', ['as' => 'academic']))->assertSee(route('tuition'));
});

test('sign-up is rate limited to 5 attempts a minute', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.store'), parentSignUp(['email' => "taken{$attempt}", 'consent' => null]))
            ->assertStatus(302);
    }

    $this->post(route('register.store'), parentSignUp())->assertStatus(429);
});
