<?php

use App\Enums\AccountStatus;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TeacherInvitation;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('the admin lists people and can view one', function () {
    $parent = User::factory()->parent()->create(['name' => 'Asha Verma']);

    Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$parent]);
    Livewire::test(ViewUser::class, ['record' => $parent->getRouteKey()])->assertOk()->assertSee('Asha Verma');
});

test('AUTH-4 the admin invites a teacher from the people list', function () {
    Notification::fake();

    Livewire::test(ListUsers::class)
        ->callAction('inviteTeacher', data: [
            'name' => 'Meera Iyer',
            'email' => 'meera@example.com',
            'offering' => 'academic',
            'subjects' => ['Thesis editing'],
        ])
        ->assertHasNoActionErrors();

    $teacher = User::where('email', 'meera@example.com')->sole();
    Notification::assertSentTo($teacher, TeacherInvitation::class);
});

test('AUTH-6 the admin deactivates and reactivates an account, and it is audited', function () {
    $parent = User::factory()->parent()->create();

    Livewire::test(ListUsers::class)->callTableAction('deactivate', $parent);
    expect($parent->fresh()->status)->toBe(AccountStatus::Deactivated);

    Livewire::test(ListUsers::class)->callTableAction('reactivate', $parent);
    expect($parent->fresh()->status)->toBe(AccountStatus::Active);

    expect(AuditLog::where('subject_id', $parent->id)->pluck('action')->all())
        ->toBe(['user.deactivated', 'user.reactivated'])
        ->and(AuditLog::first()->user_id)->toBe($this->admin->id);
});

test('AUTH-6 the admin unlocks a locked account', function () {
    $parent = User::factory()->parent()->create();
    $parent->forceFill(['locked_until' => now()->addMinutes(10), 'failed_login_attempts' => 3])->save();

    Livewire::test(ListUsers::class)->callTableAction('unlock', $parent);

    expect($parent->fresh()->isLocked())->toBeFalse();
    expect(AuditLog::where('action', 'user.unlocked')->exists())->toBeTrue();
});

test('the admin cannot deactivate their own account', function () {
    Livewire::test(ListUsers::class)->assertTableActionHidden('deactivate', $this->admin);
});

test('the make-admin command creates an admin who can reach the panel', function () {
    $this->artisan('app:make-admin')
        ->expectsQuestion('Name', 'Owner')
        ->expectsQuestion('Email address', 'owner@example.com')
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    $owner = User::where('email', 'owner@example.com')->sole();
    expect($owner->isAdmin())->toBeTrue()->and($owner->hasVerifiedEmail())->toBeTrue();
});
