<?php

use App\Enums\ConsentPurpose;
use App\Enums\Role;
use App\Filament\Resources\AcademicOrders\Pages\ViewAcademicOrder;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\NewTeacherSignUp;
use App\Notifications\TeacherApproved;
use Database\Seeders\AcademicCatalogueSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    config(['verticals.music.enabled' => true, 'site.contact.email' => 'hello@example.com']);
});

function teacherSignUp(array $overrides = []): array
{
    return [
        'account_type' => 'teacher',
        'name' => 'Pranjal Saikia',
        'email' => 'pranjal@example.com',
        'mobile' => '98765 43210',
        'password' => 'password',
        'password_confirmation' => 'password',
        'offerings' => ['tuition', 'music'],
        'subjects' => 'Maths, Science , Guitar',
        'adult' => '1',
        'teacher_rules' => '1',
        'consent' => '1',
        ...$overrides,
    ];
}

test('HIR-8 the teacher sign-up screen renders with the sections, subjects and rules', function () {
    $this->get(route('register', ['as' => 'teacher']))
        ->assertOk()
        ->assertSee('Create your teacher account')
        ->assertSee('Where do you want to teach?')
        ->assertSee('School tuition, classes 1 to 12')
        ->assertSee('Music lessons')
        ->assertSee('never write work for students to submit as their own');
});

test('HIR-8 a teacher signs up, waits for approval, and the business is emailed', function () {
    $this->post(route('register.store'), teacherSignUp())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'pranjal@example.com')->sole();
    expect($user->role)->toBe(Role::Teacher)
        ->and($user->teacher->offerings)->toBe(['tuition', 'music'])
        ->and($user->teacher->subjects)->toBe(['Maths', 'Science', 'Guitar'])
        ->and($user->teacher->mobile)->toBe('+919876543210')
        ->and($user->teacher->isApproved())->toBeFalse()
        ->and($user->consents()->sole()->purpose)->toBe(ConsentPurpose::TeacherSelf);

    Notification::assertSentOnDemand(NewTeacherSignUp::class);

    $user->markEmailAsVerified();
    $this->actingAs($user->fresh())->get(route('teacher.home'))
        ->assertOk()
        ->assertSee('Your account is waiting for approval');
});

test('HIR-8 a teacher must be 18+, choose a section and agree to the rules', function () {
    $this->post(route('register.store'), teacherSignUp(['adult' => null, 'teacher_rules' => null, 'offerings' => [], 'subjects' => '']))
        ->assertSessionHasErrors(['adult', 'teacher_rules', 'offerings', 'subjects']);

    $this->post(route('register.store'), teacherSignUp(['offerings' => ['admin']]))
        ->assertSessionHasErrors(['offerings.0']);

    expect(User::count())->toBe(0);
});

test('HIR-8 other account types can\'t send teacher fields', function () {
    $this->post(route('register.store'), teacherSignUp(['account_type' => 'parent', 'adult' => null]))
        ->assertSessionHasErrors(['offerings', 'subjects', 'teacher_rules']);

    expect(User::count())->toBe(0);
});

test('HIR-8 an unapproved teacher can\'t open assigned orders, and can\'t be given work', function () {
    config(['features.academic_orders' => true]);
    $this->seed(AcademicCatalogueSeeder::class);
    $pending = Teacher::factory()->pending()->create(['offerings' => ['academic', 'tuition']]);

    $this->actingAs($pending->user)->get(route('teacher.orders'))->assertRedirect(route('teacher.home'));

    $admin = User::factory()->admin()->create();
    $order = AcademicOrder::factory()->paid()->create();
    Livewire::actingAs($admin)->test(ViewAcademicOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('assign', data: ['expert_id' => $pending->id])
        ->assertHasActionErrors(['expert_id']);

    expect($order->fresh()->expert_id)->toBeNull();
});

test('HIR-8 the admin sees teachers waiting for approval and approves one', function () {
    $pending = Teacher::factory()->pending()->create();
    $approved = Teacher::factory()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(ListTeachers::class)
        ->assertTableActionHidden('approve', $approved)
        ->assertTableActionVisible('approve', $pending)
        ->set('activeTab', 'pending')
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved])
        ->callTableAction('approve', $pending);

    expect($pending->fresh()->isApproved())->toBeTrue()
        ->and(AuditLog::where('action', 'teacher.approved')->exists())->toBeTrue();
    Notification::assertSentTo($pending->user, TeacherApproved::class);

    Livewire::test(EditTeacher::class, ['record' => $pending->getRouteKey()])->assertActionHidden('approve');
});

test('HIR-8 an invited teacher is approved from the start', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ListUsers::class)
        ->callAction('inviteTeacher', data: ['name' => 'Meera Iyer', 'email' => 'meera@example.com', 'offerings' => ['tuition'], 'subjects' => []])
        ->assertHasNoActionErrors();

    expect(User::where('email', 'meera@example.com')->sole()->teacher->isApproved())->toBeTrue();
});
