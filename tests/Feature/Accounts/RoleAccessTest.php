<?php

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;

function userWithRole(Role $role): User
{
    return match ($role) {
        Role::Student => Student::factory()->create()->user,
        Role::Parent => User::factory()->parent()->create(),
        Role::AcademicStudent => User::factory()->academicStudent()->create(),
        Role::Teacher => User::factory()->teacher()->create(),
        Role::Admin => User::factory()->admin()->create(),
    };
}

$homes = [
    'parent' => [Role::Parent, 'parent.children'],
    'student' => [Role::Student, 'student.home'],
    'academic student' => [Role::AcademicStudent, 'academic.home'],
    'teacher' => [Role::Teacher, 'teacher.home'],
];

test('guests are sent to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('parent.children'))->assertRedirect(route('login'));
    $this->get('/admin')->assertRedirect(route('login'));
});

test('AUTH-5 the dashboard sends each role to its own home', function (Role $role, string $home) {
    $this->actingAs(userWithRole($role))
        ->get(route('dashboard'))
        ->assertRedirect(route($home));
})->with($homes);

test('AUTH-5 each role reaches its own home', function (Role $role, string $home) {
    $this->actingAs(userWithRole($role))->get(route($home))->assertOk();
})->with($homes);

test('AUTH-5 each role is refused every other role\'s home', function (Role $role, string $home) use ($homes) {
    $this->actingAs(userWithRole($role));

    foreach ($homes as [$otherRole, $otherHome]) {
        if ($otherRole !== $role) {
            $this->get(route($otherHome))->assertForbidden();
        }
    }
})->with($homes);

test('AUTH-5 only admins reach the admin panel', function (Role $role) {
    $this->actingAs(userWithRole($role))->get('/admin')->assertForbidden();
})->with([Role::Parent, Role::Student, Role::AcademicStudent, Role::Teacher]);

test('an admin reaches the admin panel and its people list', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin')->assertOk();
    $this->get('/admin/users')->assertOk();
    $this->get('/admin/audit-logs')->assertOk();
});

test('a deactivated admin is refused the admin panel', function () {
    $this->actingAs(User::factory()->admin()->deactivated()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('a parent must verify their email before using the portal', function () {
    $this->actingAs(User::factory()->parent()->unverified()->create())
        ->get(route('parent.children'))
        ->assertRedirect(route('verification.notice'));
});

test('school students have no email to verify and no profile page', function () {
    $this->actingAs(userWithRole(Role::Student));

    $this->get(route('student.home'))->assertOk();
    $this->get(route('profile.edit'))->assertForbidden();
    $this->get('/settings')->assertRedirect(route('security.edit'));
});

test('the portal is hidden while FEATURE_ACCOUNTS is off', function () {
    config(['features.accounts' => false]);

    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertNotFound();
    $this->get(route('parent.children'))->assertNotFound();
});
