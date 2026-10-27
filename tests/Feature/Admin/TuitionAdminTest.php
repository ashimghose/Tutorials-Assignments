<?php

use App\Actions\Batches\ScheduleSessions;
use App\Enums\EnrolmentStatus;
use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\Enrolments\EnrolmentActions;
use App\Filament\Resources\Enrolments\Pages\ListEnrolments;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\EnrolmentActivated;
use App\Notifications\RemovedFromBatch;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('CLS-1 the admin creates a batch, its fee is stored in paise and its classes are added', function () {
    Repeater::fake();
    $teacher = Teacher::factory()->create();

    Livewire::test(CreateBatch::class)
        ->fillForm([
            'subject' => 'Science',
            'class_level' => 8,
            'board' => 'CBSE',
            'teacher_id' => $teacher->id,
            'monthly_fee' => 1800,
            'max_seats' => 12,
            'schedule' => [['day' => 2, 'start' => '18:00', 'minutes' => 60], ['day' => 5, 'start' => '18:00', 'minutes' => 60]],
            'meet_link' => 'https://meet.google.com/aaa-bbbb-ccc',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::sole();
    expect($batch->monthly_fee)->toBe(180000)
        ->and($batch->max_seats)->toBe(12)
        ->and($batch->teacher_id)->toBe($teacher->id)
        ->and($batch->sessions()->count())->toBeGreaterThanOrEqual(8)
        ->and(AuditLog::where('action', 'batch.created')->exists())->toBeTrue();
});

test('changing the timetable replaces future weekly classes but keeps moved ones', function () {
    $batch = Batch::factory()->create(['schedule' => [['day' => 1, 'start' => '17:00', 'minutes' => 60]]]);
    app(ScheduleSessions::class)->handle($batch);
    $moved = $batch->sessions()->orderBy('starts_at')->first();
    $moved->forceFill(['starts_at' => $moved->starts_at->addHour(), 'ends_at' => $moved->ends_at->addHour()])->save();

    Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
        ->fillForm(fn (array $state) => [...$state, 'schedule' => [['day' => 3, 'start' => '16:00', 'minutes' => 60]]])
        ->call('save')
        ->assertHasNoFormErrors();

    $days = $batch->sessions()->whereKeyNot($moved->id)->get()->map(fn (ClassSession $s) => $s->starts_at->setTimezone('Asia/Kolkata')->dayOfWeekIso)->unique()->values()->all();
    expect($days)->toBe([3])->and($moved->fresh())->not->toBeNull();
});

test('PAY-2 the admin marks the fee as received, which activates the enrolment and is audited', function () {
    $enrolment = Enrolment::factory()->create();
    $until = Enrolment::today()->addMonth()->toDateString();

    Livewire::test(ListEnrolments::class)
        ->assertCanSeeTableRecords([$enrolment])
        ->callTableAction('activate', $enrolment, data: ['paid_through' => $until])
        ->assertHasNoTableActionErrors();

    expect($enrolment->fresh()->status)->toBe(EnrolmentStatus::Active)
        ->and($enrolment->fresh()->paid_through->toDateString())->toBe($until)
        ->and(AuditLog::where('action', 'enrolment.activated')->where('user_id', $this->admin->id)->exists())->toBeTrue();
    Notification::assertSentTo($enrolment->student->parent->user, EnrolmentActivated::class);
});

test('the suggested paid-until date runs a month on from the current one', function () {
    $current = Enrolment::factory()->active(10)->make();
    $lapsed = Enrolment::factory()->active(-20)->make();
    $new = Enrolment::factory()->make();
    $today = Enrolment::today();

    expect(EnrolmentActions::nextPaidThrough($current)->toDateString())->toBe($today->addDays(10)->addMonthNoOverflow()->toDateString())
        ->and(EnrolmentActions::nextPaidThrough($lapsed)->toDateString())->toBe($today->addMonthNoOverflow()->subDay()->toDateString())
        ->and(EnrolmentActions::nextPaidThrough($new)->toDateString())->toBe($today->addMonthNoOverflow()->subDay()->toDateString());
});

test('CLS-7 the admin removes a student from a batch with a reason sent to the parent', function () {
    $enrolment = Enrolment::factory()->active()->create();

    Livewire::test(ListEnrolments::class)
        ->callTableAction('remove', $enrolment, data: ['reason' => 'Moved to the Class 9 batch'])
        ->assertHasNoTableActionErrors();

    expect($enrolment->fresh()->status)->toBe(EnrolmentStatus::Removed)
        ->and($enrolment->fresh()->hasAccess())->toBeFalse();
    Notification::assertSentTo($enrolment->student->parent->user, RemovedFromBatch::class, fn ($n) => $n->enrolment->removed_reason === 'Moved to the Class 9 batch');
});

test('the admin cancels a class from the batch screen', function () {
    $session = ClassSession::factory()->create();

    Livewire::test(SessionsRelationManager::class, ['ownerRecord' => $session->batch, 'pageClass' => EditBatch::class])
        ->callTableAction('cancel', $session);

    expect($session->fresh()->isCancelled())->toBeTrue()
        ->and(AuditLog::where('action', 'class.cancelled')->exists())->toBeTrue();
});

test('the admin adds an extra class in India time', function () {
    $batch = Batch::factory()->create();

    Livewire::test(SessionsRelationManager::class, ['ownerRecord' => $batch, 'pageClass' => EditBatch::class])
        ->callTableAction('extra', data: ['starts_at' => '2026-12-05 10:00:00', 'minutes' => 90])
        ->assertHasNoTableActionErrors();

    $session = $batch->sessions()->sole();
    expect($session->starts_at->toDateTimeString())->toBe('2026-12-05 04:30:00')
        ->and($session->ends_at->toDateTimeString())->toBe('2026-12-05 06:00:00')
        ->and($session->scheduled_for)->toBeNull();
});

test('a batch with students cannot be deleted', function () {
    $enrolment = Enrolment::factory()->create();
    $empty = Batch::factory()->create();

    expect($this->admin->can('delete', $enrolment->batch))->toBeFalse()
        ->and($this->admin->can('delete', $empty))->toBeTrue();
});

test('non-admins cannot reach the tuition screens', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get('/admin/batches')->assertForbidden();
    $this->get('/admin/enrolments')->assertForbidden();
})->with(['parent', 'teacher', 'academicStudent']);
