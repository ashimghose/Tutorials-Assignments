<?php

use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\GuardianConsentRequest;
use App\Support\GoogleSignIn;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'services.google.client_id' => 'test-client.apps.googleusercontent.com',
        'services.google.client_secret' => 'test-secret',
    ]);
});

function fakeGoogleUser(array $overrides = []): GoogleUser
{
    $data = [
        'id' => '1234567890',
        'name' => 'Asha Verma',
        'email' => 'Asha@Example.com',
        'email_verified' => true,
        ...$overrides,
    ];

    $user = (new GoogleUser)->setRaw($data)->map([
        'id' => $data['id'],
        'name' => $data['name'],
        'email' => $data['email'],
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($user);

    return $user;
}

function googleFinish(array $overrides = []): array
{
    return [
        'account_type' => 'parent',
        'name' => 'Asha Verma',
        'mobile' => '98765 43210',
        'consent' => '1',
        ...$overrides,
    ];
}

test('AUTH-7 the Google button shows only when the Google client is set', function () {
    $this->get(route('login'))->assertOk()->assertSee('Continue with Google');
    $this->get(route('register', ['as' => 'academic']))->assertOk()->assertSee('Sign up with Google')
        ->assertSee(route('google.redirect', ['as' => 'academic']), false);

    config(['services.google.client_secret' => null]);

    $this->get(route('login'))->assertOk()->assertDontSee('Continue with Google');
    $this->get(route('google.redirect'))->assertNotFound();
});

test('AUTH-7 an existing account logs in with Google and is linked to it', function () {
    $user = User::factory()->academicStudent()->unverified()->create(['email' => 'asha@example.com']);
    fakeGoogleUser();

    $this->get(route('google.callback'))->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $user->refresh();
    expect($user->google_id)->toBe('1234567890')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->last_login_at)->not->toBeNull();
});

test('AUTH-7 a linked Google account logs in even after the email changed', function () {
    $user = User::factory()->create(['email' => 'new@example.com']);
    $user->forceFill(['google_id' => '1234567890'])->save();
    fakeGoogleUser();

    $this->get(route('google.callback'));

    $this->assertAuthenticatedAs($user);
});

test('AUTH-7 Google sign-in is refused for unverified emails, school students, deactivated, locked and differently linked accounts', function (Closure $setup, array $google) {
    $setup();
    fakeGoogleUser($google);

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

    $this->assertGuest();
})->with([
    'unverified Google email' => [fn () => User::factory()->create(['email' => 'asha@example.com']), ['email_verified' => false]],
    'school student' => [fn () => User::factory()->student()->create(['email' => 'asha@example.com']), []],
    'deactivated' => [fn () => User::factory()->deactivated()->create(['email' => 'asha@example.com']), []],
    'locked' => [fn () => User::factory()->create(['email' => 'asha@example.com'])->forceFill(['locked_until' => now()->addMinutes(10)])->save(), []],
    'other Google account' => [fn () => User::factory()->create(['email' => 'asha@example.com'])->forceFill(['google_id' => '999'])->save(), []],
]);

test('AUTH-7 a cancelled Google screen goes back to the login page', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('access_denied'));

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('AUTH-7 two-factor still applies to Google sign-in', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]);

    $user = User::factory()->withTwoFactor()->create(['email' => 'asha@example.com']);
    fakeGoogleUser();

    $this->get(route('google.callback'))->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
    expect(session('login.id'))->toBe($user->id);
});

test('AUTH-7 a new person must finish the sign-up questions before an account is made', function () {
    $this->get(route('google.redirect', ['as' => 'academic']));
    fakeGoogleUser();

    $this->get(route('google.callback'))->assertRedirect(route('google.finish', ['as' => 'academic']));

    expect(User::count())->toBe(0);
    $this->assertGuest();

    $this->get(route('google.finish', ['as' => 'academic']))->assertOk()
        ->assertSee('Signed in with Google as asha@example.com')
        ->assertSee('I am 18 or older')
        ->assertDontSee('Confirm password');
});

test('AUTH-7 a new person coming from the login page is asked who they are first', function () {
    config(['verticals.music.enabled' => true]);
    $this->get(route('google.redirect'));
    fakeGoogleUser();

    $this->get(route('google.callback'))->assertRedirect(route('google.finish'));

    $this->get(route('google.finish'))->assertOk()
        ->assertSee('Who are you?')
        ->assertSee(route('google.finish', ['as' => 'parent']), false)
        ->assertSee(route('google.finish', ['as' => 'academic']), false)
        ->assertSee(route('google.finish', ['as' => 'music']), false)
        ->assertDontSee('Mobile number');

    $this->get(route('google.finish', ['as' => 'parent']))->assertOk()->assertSee('Create a parent account')->assertSee('Mobile number');
});

test('AUTH-7 AUTH-1 a parent finishes Google sign-up with mobile and consent, already verified', function () {
    session([GoogleSignIn::SESSION_KEY => ['id' => '1234567890', 'name' => 'Asha Verma', 'email' => 'asha@example.com']]);

    $this->post(route('google.finish.store'), googleFinish(['email' => 'attacker@example.com', 'google_id' => '1']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user = User::sole();
    $this->assertAuthenticatedAs($user);
    expect($user->email)->toBe('asha@example.com')
        ->and($user->google_id)->toBe('1234567890')
        ->and($user->role)->toBe(Role::Parent)
        ->and($user->password)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->parentProfile->mobile)->toBe('9876543210')
        ->and($user->consents()->sole()->purpose)->toBe(ConsentPurpose::ParentAccount)
        ->and(session(GoogleSignIn::SESSION_KEY))->toBeNull();

    $this->get(route('dashboard'))->assertRedirect(route('parent.children'));
});

test('AUTH-7 ACA-3 Google sign-up keeps the consent and 18+ rules', function () {
    session([GoogleSignIn::SESSION_KEY => ['id' => '1234567890', 'name' => 'Asha Verma', 'email' => 'asha@example.com']]);

    $this->post(route('google.finish.store'), googleFinish(['account_type' => 'academic', 'consent' => null]))
        ->assertSessionHasErrors(['consent', 'adult']);

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

test('AUTH-7 MUS-3 an under-18 music learner joining with Google still needs a parent\'s approval', function () {
    Notification::fake();
    config(['verticals.music.enabled' => true]);
    session([GoogleSignIn::SESSION_KEY => ['id' => '1234567890', 'name' => 'Rohan Das', 'email' => 'rohan@example.com']]);

    $this->post(route('google.finish.store'), googleFinish([
        'account_type' => 'music',
        'name' => 'Rohan Das',
        'age_group' => 'minor',
        'guardian_email' => 'parent@example.com',
    ]))->assertSessionHasNoErrors();

    $user = User::sole();
    expect($user->role)->toBe(Role::MusicStudent)
        ->and($user->student->guardian_email)->toBe('parent@example.com')
        ->and($user->student->adult_declared_at)->toBeNull();

    Notification::assertSentOnDemandTimes(GuardianConsentRequest::class, 1);
});

test('AUTH-7 the finish page needs a Google sign-in first', function () {
    $this->get(route('google.finish'))->assertRedirect(route('register'));
    $this->post(route('google.finish.store'), googleFinish())->assertRedirect(route('register'));

    expect(User::count())->toBe(0);
});

test('AUTH-7 someone who joined with Google can set a password without a current one', function () {
    $user = User::factory()->create(['password' => null]);

    Livewire::actingAs($user)->test('pages::settings.security')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
});

test('AUTH-7 a Google-only account confirms it is them with the same Google account', function () {
    $user = User::factory()->create(['password' => null]);
    $user->forceFill(['google_id' => '1234567890'])->save();

    $this->actingAs($user)->get(route('password.confirm'))->assertOk()->assertSee('Confirm with Google');
    $this->get(route('google.confirm'))->assertRedirect();
    fakeGoogleUser();

    $this->get(route('google.callback'))->assertRedirect(route('dashboard', absolute: false));

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('AUTH-7 confirming with a different Google account is refused', function () {
    $user = User::factory()->create(['password' => null]);
    $user->forceFill(['google_id' => '555'])->save();

    $this->actingAs($user)->get(route('google.confirm'));
    fakeGoogleUser();

    $this->get(route('google.callback'))->assertRedirect(route('password.confirm'))->assertSessionHasErrors('password');

    expect(session('auth.password_confirmed_at'))->toBeNull();
});

test('AUTH-7 a Google-only account deletes itself by typing DELETE', function () {
    $user = User::factory()->create(['password' => null]);
    $this->actingAs($user);

    Livewire::test('pages::settings.delete-user-modal')->set('password', 'yes')->call('deleteUser')->assertHasErrors('password');
    expect($user->fresh())->not->toBeNull();

    Livewire::test('pages::settings.delete-user-modal')->set('password', 'DELETE')->call('deleteUser')->assertHasNoErrors();
    expect($user->fresh())->toBeNull();
});

