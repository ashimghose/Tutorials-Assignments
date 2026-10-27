<?php

use App\Actions\Enrolments\ActivateEnrolment;
use App\Enums\EnrolmentStatus;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Enrolment;
use App\Models\User;
use App\Notifications\AccessPaused;
use App\Notifications\EnrolmentActivated;
use App\Notifications\EnrolmentRequested;
use App\Notifications\FeeReminder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->child = tuitionChild(['class_level' => 10]);
    $this->parent = $this->child->parent->user;
    $this->batch = Batch::factory()->create(['class_level' => 10]);
});

test('CLS-1 a parent sees open batches for their child\'s class and asks for a place', function () {
    $admin = User::factory()->admin()->create();
    $otherClass = Batch::factory()->create(['class_level' => 4, 'subject' => 'EVS']);

    Livewire::actingAs($this->parent)->test('pages::portal.parent.classes')
        ->assertSee($this->batch->subject)
        ->assertSee('₹1,500 a month')
        ->assertDontSee('EVS')
        ->call('requestSeat', $this->child->id, $this->batch->id)
        ->assertHasNoErrors()
        ->assertSee('Waiting for fee');

    $enrolment = Enrolment::sole();
    expect($enrolment->status)->toBe(EnrolmentStatus::Pending)
        ->and($enrolment->paid_through)->toBeNull();
    Notification::assertSentTo($admin, EnrolmentRequested::class);
});

test('a parent cannot ask for a place for someone else\'s child', function () {
    $stranger = tuitionChild(['class_level' => 10]);

    Livewire::actingAs($this->parent)->test('pages::portal.parent.classes')
        ->call('requestSeat', $stranger->id, $this->batch->id)
        ->assertForbidden();

    expect(Enrolment::count())->toBe(0);
});

test('a parent cannot ask for a place without consent, in a batch for another class, or twice', function () {
    $this->child->consents()->update(['withdrawn_at' => now()]);
    Livewire::actingAs($this->parent)->test('pages::portal.parent.classes')
        ->call('requestSeat', $this->child->id, $this->batch->id)
        ->assertForbidden();

    $child = tuitionChild(['class_level' => 10]);
    $parent = $child->parent->user;
    $wrongClass = Batch::factory()->create(['class_level' => 9]);
    Livewire::actingAs($parent)->test('pages::portal.parent.classes')
        ->call('requestSeat', $child->id, $wrongClass->id)
        ->assertForbidden();

    $page = Livewire::actingAs($parent)->test('pages::portal.parent.classes')
        ->call('requestSeat', $child->id, $this->batch->id)
        ->call('requestSeat', $child->id, $this->batch->id)
        ->assertHasErrors('batch');
    expect(Enrolment::count())->toBe(1);
});

test('CLS-1 a full batch takes no more requests', function () {
    $this->batch->update(['max_seats' => 1]);
    Enrolment::factory()->for($this->batch)->create();

    Livewire::actingAs($this->parent)->test('pages::portal.parent.classes')
        ->assertSee('Full')
        ->call('requestSeat', $this->child->id, $this->batch->id)
        ->assertHasErrors('batch');

    expect($this->child->enrolments()->count())->toBe(0);
});

test('a parent cancels a waiting request but not an active place', function () {
    $pending = Enrolment::factory()->for($this->child)->for($this->batch)->create();
    $active = Enrolment::factory()->for($this->child)->for(Batch::factory()->create(['class_level' => 10]))->active()->create();

    $page = Livewire::actingAs($this->parent)->test('pages::portal.parent.classes')
        ->call('cancelRequest', $pending->id)
        ->assertHasNoErrors();
    expect(Enrolment::find($pending->id))->toBeNull();

    $page->call('cancelRequest', $active->id)->assertForbidden();
    expect($active->fresh())->not->toBeNull();
});

test('PAY-2 a fee confirmation makes the enrolment active and emails the parent', function () {
    $enrolment = Enrolment::factory()->for($this->child)->for($this->batch)->create();

    app(ActivateEnrolment::class)->handle($enrolment, Enrolment::today()->addMonth());

    expect($enrolment->fresh()->status)->toBe(EnrolmentStatus::Active)
        ->and($enrolment->fresh()->hasAccess())->toBeTrue()
        ->and(AuditLog::where('action', 'enrolment.activated')->exists())->toBeTrue();
    Notification::assertSentTo($this->parent, EnrolmentActivated::class);
});

test('PAY-4 access lasts until three days after the paid-through date', function (int $paidDaysAgo, bool $access) {
    $enrolment = Enrolment::factory()->for($this->child)->for($this->batch)->active(-$paidDaysAgo)->create();

    expect($enrolment->hasAccess())->toBe($access)
        ->and(Enrolment::withAccess()->whereKey($enrolment->id)->exists())->toBe($access);
})->with([
    'paid until today' => [0, true],
    'two days late' => [2, true],
    'three days late' => [3, true],
    'four days late' => [4, false],
]);

test('a pending or removed enrolment gives no access', function () {
    $pending = Enrolment::factory()->for($this->child)->for($this->batch)->create();
    $removed = Enrolment::factory()->active()->create(['status' => EnrolmentStatus::Removed]);

    expect($pending->hasAccess())->toBeFalse()->and($removed->hasAccess())->toBeFalse();
});

test('PAY-4 the daily check sends reminders 5 days and 1 day before, once, and pauses after the grace period', function () {
    $five = Enrolment::factory()->for($this->child)->for($this->batch)->active(5)->create();
    $one = Enrolment::factory()->active(1)->create();
    $three = Enrolment::factory()->active(3)->create();
    $lapsed = Enrolment::factory()->active(-4)->create();
    $grace = Enrolment::factory()->active(-3)->create();

    $this->artisan('app:check-enrolments')->assertSuccessful();
    $this->artisan('app:check-enrolments')->assertSuccessful();

    Notification::assertSentToTimes($this->parent, FeeReminder::class, 1);
    Notification::assertSentTo($one->student->parent->user, FeeReminder::class, fn (FeeReminder $n) => $n->daysLeft === 1);
    Notification::assertNotSentTo($three->student->parent->user, FeeReminder::class);

    expect($lapsed->fresh()->status)->toBe(EnrolmentStatus::Expired)
        ->and($grace->fresh()->status)->toBe(EnrolmentStatus::Active);
    Notification::assertSentToTimes($lapsed->student->parent->user, AccessPaused::class, 1);
    Notification::assertNotSentTo($grace->student->parent->user, AccessPaused::class);
});

test('only parents reach the classes page', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('parent.classes'))
        ->assertForbidden();
})->with(['academicStudent', 'teacher', 'admin']);

test('the classes page is off without the feature flag', function () {
    config(['features.batches' => false]);

    $this->actingAs($this->parent)->get('/parent/classes')->assertNotFound();
});
