<?php

use App\Enums\AccountStatus;
use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Models\Consent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function childForm(array $overrides = []): array
{
    return [
        'first_name' => 'Riya',
        'class_level' => '7',
        'board' => 'CBSE',
        'school' => '',
        'username' => 'Riya2026',
        'password' => 'password',
        'password_confirmation' => 'password',
        'consent' => true,
        ...$overrides,
    ];
}

function fillChildForm($component, array $values)
{
    foreach ($values as $field => $value) {
        $component->set($field, $value);
    }

    return $component;
}

function childWithConsent(?User $parent = null): Student
{
    $student = Student::factory()->create($parent ? ['parent_id' => $parent->parentProfile->id] : []);
    Consent::record($student->parent->user, ConsentPurpose::ChildData, $student);

    return $student;
}

test('AUTH-3 a parent adds a child with consent and creates the child\'s login', function () {
    $parent = User::factory()->parent()->create();
    $this->actingAs($parent);

    fillChildForm(Livewire::test('pages::portal.parent.children'), childForm())
        ->call('addChild')
        ->assertHasNoErrors();

    $student = $parent->parentProfile->students()->sole();
    expect($student->first_name)->toBe('Riya')
        ->and($student->class_level)->toBe(7)
        ->and($student->user->role)->toBe(Role::Student)
        ->and($student->user->username)->toBe('riya2026')
        ->and($student->user->email)->toBeNull();

    $consent = Consent::sole();
    expect($consent->purpose)->toBe(ConsentPurpose::ChildData)
        ->and($consent->student_id)->toBe($student->id)
        ->and($consent->user_id)->toBe($parent->id)
        ->and($consent->policy_version)->toBe(config('privacy.policy_version'));
});

test('AUTH-3 a child cannot be added without consent or with a taken username', function () {
    User::factory()->student()->create(['username' => 'riya2026']);
    $this->actingAs(User::factory()->parent()->create());

    fillChildForm(Livewire::test('pages::portal.parent.children'), childForm(['consent' => false, 'class_level' => '13']))
        ->call('addChild')
        ->assertHasErrors(['consent', 'username', 'class_level']);

    expect(Student::count())->toBe(0);
});

test('only a parent with a verified email can add a child', function () {
    $this->actingAs(User::factory()->parent()->unverified()->create());

    fillChildForm(Livewire::test('pages::portal.parent.children'), childForm())
        ->call('addChild')
        ->assertForbidden();
});

test('a parent sees only their own children', function () {
    $mine = childWithConsent(User::factory()->parent()->create());
    $mine->update(['first_name' => 'Aarav']);
    $theirs = childWithConsent();
    $theirs->update(['first_name' => 'Zoya']);

    $this->actingAs($mine->parent->user)
        ->get(route('parent.children'))
        ->assertOk()
        ->assertSee('Aarav')
        ->assertDontSee('Zoya');
});

test('AUTH-3 a parent resets their own child\'s password and clears a lock', function () {
    $student = childWithConsent();
    $student->user->forceFill(['locked_until' => now()->addMinutes(10)])->save();
    $this->actingAs($student->parent->user);

    Livewire::test('pages::portal.parent.children')
        ->call('startReset', $student->id)
        ->set('new_password', 'new-password')
        ->set('new_password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasNoErrors();

    $login = $student->user->fresh();
    expect(Hash::check('new-password', $login->password))->toBeTrue()
        ->and($login->isLocked())->toBeFalse();
});

test('a parent cannot reset or change another family\'s child', function () {
    $theirs = childWithConsent();
    $this->actingAs(User::factory()->parent()->create());

    Livewire::test('pages::portal.parent.children')->call('startReset', $theirs->id)->assertForbidden();

    Livewire::test('pages::portal.parent.children')
        ->set('resetStudentId', $theirs->id)
        ->set('new_password', 'new-password')
        ->set('new_password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertForbidden();

    Livewire::test('pages::portal.parent.children')->call('withdrawConsent', $theirs->id)->assertForbidden();

    expect(Hash::check('password', $theirs->user->fresh()->password))->toBeTrue()
        ->and($theirs->hasActiveConsent())->toBeTrue();
});

test('withdrawing consent switches the child\'s login off, and giving it again switches it back on', function () {
    $student = childWithConsent();
    $this->actingAs($student->parent->user);

    Livewire::test('pages::portal.parent.children')->call('withdrawConsent', $student->id);

    expect($student->hasActiveConsent())->toBeFalse()
        ->and($student->user->fresh()->status)->toBe(AccountStatus::Deactivated)
        ->and(Consent::whereNotNull('withdrawn_at')->count())->toBe(1);

    Livewire::test('pages::portal.parent.children')->call('giveConsent', $student->id);

    expect($student->hasActiveConsent())->toBeTrue()
        ->and($student->user->fresh()->status)->toBe(AccountStatus::Active);
});

test('deleting a parent account removes their children and the children\'s logins', function () {
    $student = childWithConsent();
    $loginId = $student->user_id;

    $student->parent->user->delete();

    expect(Student::count())->toBe(0)
        ->and(User::find($loginId))->toBeNull();
});
