<?php

use App\Enums\AccountStatus;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Enrolment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\TeacherInvitation;
use Database\Seeders\AcademicCatalogueSeeder;
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
            'offerings' => ['tuition', 'academic'],
            'subjects' => ['Thesis editing'],
        ])
        ->assertHasNoActionErrors();

    $teacher = User::where('email', 'meera@example.com')->sole();
    expect($teacher->teacher->offerings)->toBe(['tuition', 'academic']);
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

test('ADM-7 the admin deletes an unused account, such as a test sign-up, and it is audited', function () {
    $parent = User::factory()->parent()->create();

    Livewire::test(ViewUser::class, ['record' => $parent->getRouteKey()])
        ->callAction('delete')
        ->assertRedirect(route('filament.admin.resources.users.index'));

    expect(User::find($parent->id))->toBeNull();
    $log = AuditLog::where('action', 'user.deleted')->sole();
    expect($log->subject_id)->toBe($parent->id)
        ->and($log->properties)->toBe(['role' => 'parent'])
        ->and($log->user_id)->toBe($this->admin->id);
});

test('ADM-7 a test family is deleted with its children, and a test child with their details', function () {
    $parent = User::factory()->parent()->create();
    $child = Student::factory()->for($parent->parentProfile, 'parent')->for(User::factory()->student())->create();
    $other = Student::factory()->for(User::factory()->student())->create();

    Livewire::test(ListUsers::class)->callTableAction('delete', $other->user);
    expect(Student::find($other->id))->toBeNull();

    Livewire::test(ListUsers::class)->callTableAction('delete', $parent);
    expect(User::find($parent->id))->toBeNull()
        ->and(User::find($child->user_id))->toBeNull()
        ->and(Student::find($child->id))->toBeNull();
});

test('ADM-7 AUTH-6 accounts with children, orders or messages, staff and the admin can only be deactivated', function (Closure $make) {
    $user = $make();

    Livewire::test(ListUsers::class)->assertTableActionHidden('delete', $user);
    expect($this->admin->can('delete', $user))->toBeFalse();
})->with([
    'parent whose child has a class place' => [fn () => tap(User::factory()->parent()->create(), fn (User $u) => Enrolment::factory()->for(Student::factory()->for($u->parentProfile, 'parent'))->create())],
    'school student with a class place' => [fn () => tap(User::factory()->student()->create(), fn (User $u) => Enrolment::factory()->for(Student::factory()->for($u))->create())],
    'parent with a conversation about their child' => [fn () => tap(User::factory()->parent()->create(), fn (User $u) => Conversation::factory()->create(['student_id' => Student::factory()->for($u->parentProfile, 'parent')->create()->id]))],
    'academic student with an order' => [fn () => tap(User::factory()->academicStudent()->create(), function (User $u) {
        test()->seed(AcademicCatalogueSeeder::class);
        AcademicOrder::factory()->for($u->academicStudent)->create();
    })],
    'parent with a conversation' => [fn () => tap(User::factory()->parent()->create(), fn (User $u) => Conversation::factory()->startedBy($u)->create())],
    'teacher' => [fn () => User::factory()->teacher()->create()],
    'the admin themself' => [fn () => test()->admin],
]);

test('the make-admin command creates an admin who can reach the panel', function () {
    $this->artisan('app:make-admin')
        ->expectsQuestion('Name', 'Owner')
        ->expectsQuestion('Email address', 'owner@example.com')
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    $owner = User::where('email', 'owner@example.com')->sole();
    expect($owner->isAdmin())->toBeTrue()->and($owner->hasVerifiedEmail())->toBeTrue();
});
