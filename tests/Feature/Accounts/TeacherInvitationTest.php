<?php

use App\Actions\Accounts\InviteTeacher;
use App\Enums\Offering;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TeacherInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function invitedTeacher(): User
{
    return app(InviteTeacher::class)('Meera Iyer', 'meera@example.com', Offering::Both, ['Maths']);
}

test('AUTH-4 inviting a teacher creates an account with no password and emails a link', function () {
    Notification::fake();
    $this->actingAs(User::factory()->admin()->create());

    $teacher = invitedTeacher();

    expect($teacher->role)->toBe(Role::Teacher)
        ->and($teacher->password)->toBeNull()
        ->and($teacher->teacher->offering)->toBe(Offering::Both)
        ->and($teacher->teacher->subjects)->toBe(['Maths']);

    Notification::assertSentTo($teacher, TeacherInvitation::class);
    expect(AuditLog::where('action', 'teacher.invited')->where('subject_id', $teacher->id)->exists())->toBeTrue();
});

test('AUTH-4 the teacher sets their own password from the link and lands on their home', function () {
    Notification::fake();
    $teacher = invitedTeacher();
    $url = TeacherInvitation::url($teacher);

    $this->get($url)->assertOk()->assertSee('Meera Iyer');

    $this->post($url, ['password' => 'teacher-pass', 'password_confirmation' => 'teacher-pass'])
        ->assertRedirect(route('dashboard'));

    $teacher->refresh();
    expect(Hash::check('teacher-pass', $teacher->password))->toBeTrue()
        ->and($teacher->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($teacher);
    $this->get(route('dashboard'))->assertRedirect(route('teacher.home'));
});

test('AUTH-4 an invitation link works only once', function () {
    Notification::fake();
    $teacher = invitedTeacher();
    $url = TeacherInvitation::url($teacher);

    $this->post($url, ['password' => 'teacher-pass', 'password_confirmation' => 'teacher-pass']);
    auth()->logout();

    $this->get($url)->assertGone();
    $this->post($url, ['password' => 'other-pass', 'password_confirmation' => 'other-pass'])->assertGone();
    expect(Hash::check('teacher-pass', $teacher->fresh()->password))->toBeTrue();
});

test('AUTH-4 a tampered or expired invitation link is refused', function () {
    Notification::fake();
    $teacher = invitedTeacher();
    $other = User::factory()->teacher()->create(['password' => null]);

    $this->get(str_replace("/invitation/{$teacher->id}", "/invitation/{$other->id}", TeacherInvitation::url($teacher)))
        ->assertForbidden();

    $url = TeacherInvitation::url($teacher);
    $this->travel(TeacherInvitation::VALID_DAYS + 1)->days();
    $this->get($url)->assertForbidden();
});
