<?php

use App\Enums\AccountStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('AUTH-3 a school student logs in with their username', function () {
    $student = Student::factory()->create();
    $student->user->forceFill(['username' => 'riya10'])->save();

    $this->post(route('login.store'), ['email' => 'riya10', 'password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($student->user);
    $this->get(route('dashboard'))->assertRedirect(route('student.home'));
    expect($student->user->fresh()->last_login_at)->not->toBeNull();
});

test('AUTH-6 five wrong passwords lock the account for 15 minutes', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        // Fortify's own per-minute throttle would also stop a 6th try; clear
        // it so this test sees only the account lock.
        cache()->clear();
    }

    expect($user->fresh()->isLocked())->toBeTrue();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->travel(16)->minutes();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

test('AUTH-6 a successful login resets the failed attempts', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    expect($user->fresh()->failed_login_attempts)->toBe(1);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

test('AUTH-6 a deactivated account cannot log in', function () {
    $user = User::factory()->deactivated()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'This account has been deactivated. Please contact us.']);

    $this->assertGuest();
});

test('AUTH-6 an account deactivated while logged in is logged out on the next page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->forceFill(['status' => AccountStatus::Deactivated])->save();

    $this->get(route('parent.children'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('an invited teacher cannot log in before setting a password', function () {
    $user = User::factory()->teacher()->create(['password' => null]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => ''])
        ->assertSessionHasErrors('password');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'anything'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('AUTH-2 a reset link works once and expires after an hour', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $reset = fn (string $password) => $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $reset('new-password-1')->assertSessionHasNoErrors();
    expect(Hash::check('new-password-1', $user->fresh()->password))->toBeTrue();

    $reset('new-password-2')->assertSessionHasErrors('email');
    expect(Hash::check('new-password-1', $user->fresh()->password))->toBeTrue();

    // A fresh link, used after 61 minutes, is refused.
    cache()->clear();
    $this->post(route('password.email'), ['email' => $user->email]);
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });
    $this->travel(61)->minutes();

    $reset('new-password-3')->assertSessionHasErrors('email');
    expect(config('auth.passwords.users.expire'))->toBe(60);
});
