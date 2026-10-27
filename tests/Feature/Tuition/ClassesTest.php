<?php

use App\Actions\Batches\ChangeClass;
use App\Actions\Batches\ScheduleSessions;
use App\Enums\ClassSessionStatus;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\User;
use App\Notifications\ClassChanged;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->batch = Batch::factory()->create();
    $this->child = tuitionChild(['class_level' => 10]);
    $this->enrolment = Enrolment::factory()->for($this->child)->for($this->batch)->active()->create();
    $this->student = $this->child->user;
    $this->teacher = $this->batch->teacher->user;
});

test('CLS-2 weekly classes are added in India time, once', function () {
    // Monday 26 Oct 2026, 10:00 in India.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 10:00', 'Asia/Kolkata'));
    $batch = Batch::factory()->create(['schedule' => [['day' => 1, 'start' => '17:00', 'minutes' => 60], ['day' => 4, 'start' => '07:30', 'minutes' => 90]]]);

    $created = app(ScheduleSessions::class)->handle($batch, 7);
    expect($created)->toBe(3)
        ->and(app(ScheduleSessions::class)->handle($batch, 7))->toBe(0);

    $first = $batch->sessions()->orderBy('starts_at')->first();
    expect($first->starts_at->toDateTimeString())->toBe('2026-10-26 11:30:00')
        ->and($first->ends_at->toDateTimeString())->toBe('2026-10-26 12:30:00');
    expect($batch->sessions()->orderBy('starts_at')->get()->map(fn ($s) => $s->starts_at->setTimezone('Asia/Kolkata')->format('D H:i'))->all())
        ->toBe(['Mon 17:00', 'Thu 07:30', 'Mon 17:00']);
});

test('CLS-2 a moved class is not added again by the schedule', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 10:00', 'Asia/Kolkata'));
    app(ScheduleSessions::class)->handle($this->batch, 3);
    $session = $this->batch->sessions()->orderBy('starts_at')->first();

    Notification::fake();
    app(ChangeClass::class)->move($session, $session->starts_at->addDay(), 45);

    expect(app(ScheduleSessions::class)->handle($this->batch, 3))->toBe(0)
        ->and($session->fresh()->ends_at->diffInMinutes($session->fresh()->starts_at, true))->toEqual(45);
});

test('CLS-2 an inactive batch gets no classes', function () {
    $this->batch->update(['is_active' => false]);

    expect(app(ScheduleSessions::class)->handle($this->batch))->toBe(0);
});

test('CLS-3 an enrolled student joins from 10 minutes before the start and goes to the Meet link', function () {
    $session = ClassSession::factory()->for($this->batch)->startingIn(9)->create();

    $this->actingAs($this->student)
        ->get(route('classes.join', $session))
        ->assertRedirect('https://meet.google.com/abc-defg-hij');
});

test('CLS-2 Join is closed earlier than 10 minutes before and after the end', function (int $startsIn) {
    $session = ClassSession::factory()->for($this->batch)->startingIn($startsIn)->create();

    $this->actingAs($this->student)->get(route('classes.join', $session))->assertForbidden();
})->with(['too early' => [11], 'finished' => [-61]]);

test('CLS-3 students without access cannot join', function (string $case) {
    $session = ClassSession::factory()->for($this->batch)->startingIn(0)->create();
    $user = match ($case) {
        'not enrolled' => tuitionChild()->user,
        'lapsed fee' => tap($this->student, fn () => $this->enrolment->forceFill(['paid_through' => Enrolment::today()->subDays(4)])->save()),
        'removed' => tap($this->student, fn () => $this->enrolment->forceFill(['status' => 'removed'])->save()),
        'parent' => $this->child->parent->user,
        'other teacher' => Batch::factory()->create()->teacher->user,
    };

    $this->actingAs($user)->get(route('classes.join', $session))->assertForbidden();
})->with(['not enrolled', 'lapsed fee', 'removed', 'parent', 'other teacher']);

test('a cancelled class cannot be joined', function () {
    $session = ClassSession::factory()->for($this->batch)->startingIn(0)->create();
    $session->forceFill(['status' => ClassSessionStatus::Cancelled])->save();

    $this->actingAs($this->student)->get(route('classes.join', $session))->assertForbidden();
});

test('the teacher joins their class, which marks it as started', function () {
    $session = ClassSession::factory()->for($this->batch)->startingIn(5)->create();

    $this->actingAs($this->teacher)->get(route('classes.join', $session))->assertRedirect('https://meet.google.com/abc-defg-hij');
    expect($session->fresh()->started_at)->not->toBeNull();
});

test('a class\'s own Meet link wins over the batch link', function () {
    $session = ClassSession::factory()->for($this->batch)->startingIn(0)->create(['meet_link' => 'https://meet.google.com/xyz-xyzx-xyz']);

    $this->actingAs($this->student)->get(route('classes.join', $session))->assertRedirect('https://meet.google.com/xyz-xyzx-xyz');
});

test('CLS-2 the student timetable shows only their batches, with Join open near the start', function () {
    ClassSession::factory()->for($this->batch)->startingIn(5)->create();
    $other = Batch::factory()->create(['subject' => 'Sanskrit']);
    ClassSession::factory()->for($other)->startingIn(5)->create();

    Livewire::actingAs($this->student)->test('pages::portal.student.home')
        ->assertSee('Class 10 Maths (CBSE)')
        ->assertDontSee('Sanskrit')
        ->assertSee(route('classes.join', ClassSession::first()))
        ->assertDontSee('meet.google.com');
});

test('a paused student is told to ask their parent, and sees no classes', function () {
    $this->enrolment->forceFill(['status' => 'expired'])->save();
    ClassSession::factory()->for($this->batch)->startingIn(5)->create();

    Livewire::actingAs($this->student)->test('pages::portal.student.home')
        ->assertSee('Ask your parent')
        ->assertDontSee(route('classes.join', ClassSession::first()));
});

test('CLS-2 the teacher sees their own batches and classes only', function () {
    ClassSession::factory()->for($this->batch)->startingIn(60)->create();
    $other = Batch::factory()->create(['subject' => 'Sanskrit']);
    ClassSession::factory()->for($other)->startingIn(60)->create();

    Livewire::actingAs($this->teacher)->test('pages::portal.teacher.home')
        ->assertSee('Class 10 Maths (CBSE)')
        ->assertSee($this->child->first_name)
        ->assertDontSee('Sanskrit');
});

test('NOT-1 cancelling a class emails the teacher and the parents of children with access', function () {
    Notification::fake();
    $paused = Enrolment::factory()->for($this->batch)->active(-10)->create();
    $session = ClassSession::factory()->for($this->batch)->create();

    app(ChangeClass::class)->cancel($session);

    expect($session->fresh()->isCancelled())->toBeTrue();
    Notification::assertSentTo([$this->child->parent->user, $this->teacher], ClassChanged::class);
    Notification::assertNotSentTo($paused->student->parent->user, ClassChanged::class);
    Notification::assertNotSentTo($this->student, ClassChanged::class);
});

test('the join link needs a login', function () {
    $session = ClassSession::factory()->for($this->batch)->startingIn(0)->create();

    $this->get(route('classes.join', $session))->assertRedirect(route('login'));
});

test('admins can open a class to check it', function () {
    $session = ClassSession::factory()->for($this->batch)->startingIn(0)->create();

    $this->actingAs(User::factory()->admin()->create())->get(route('classes.join', $session))->assertRedirect();
});
