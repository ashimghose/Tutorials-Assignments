<?php

use App\Actions\Enrolments\ActivateEnrolment;
use App\Enums\ConsentPurpose;
use App\Enums\EnrolmentStatus;
use App\Enums\NoticeAudience;
use App\Enums\Offering;
use App\Enums\Role;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Conversation;
use App\Models\Enrolment;
use App\Models\Notice;
use App\Models\Student;
use App\Models\User;
use App\Notifications\EnrolmentActivated;
use App\Notifications\EnrolmentRequested;
use App\Notifications\GuardianConsentRequest;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    config(['verticals.music.enabled' => true]);
});

function musicSignUp(array $overrides = []): array
{
    return [
        'account_type' => 'music',
        'name' => 'Rohan Das',
        'email' => 'rohan@example.com',
        'mobile' => '98765 43210',
        'password' => 'password',
        'password_confirmation' => 'password',
        'age_group' => 'adult',
        'adult' => '1',
        'consent' => '1',
        ...$overrides,
    ];
}

function consentUrl(Student $learner): string
{
    return URL::temporarySignedRoute('music.consent.show', now()->addDays(14), ['student' => $learner->id]);
}

test('MUS-3 the music sign-up screen renders with the age question', function () {
    $this->get(route('register', ['as' => 'music']))
        ->assertOk()
        ->assertSee('Create your music account')
        ->assertSee('I am under 18')
        ->assertSee('Music learner');
});

test('MUS-3 the music tab is hidden while the music section is off', function () {
    config(['verticals.music.enabled' => false]);

    $this->get(route('register', ['as' => 'music']))->assertOk()->assertDontSee('Music learner')->assertSee('Create a parent account');
    $this->post(route('register.store'), musicSignUp())->assertSessionHasErrors('account_type');
    expect(User::count())->toBe(0);
});

test('MUS-3 an adult music learner signs up directly, with no parent account', function () {
    $this->post(route('register.store'), musicSignUp(['marketing' => '1']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'rohan@example.com')->sole();
    $learner = $user->student;

    expect($user->role)->toBe(Role::MusicStudent)
        ->and($user->parentProfile)->toBeNull()
        ->and($user->marketing_opt_in_at)->not->toBeNull()
        ->and($learner->isMusicLearner())->toBeTrue()
        ->and($learner->first_name)->toBe('Rohan')
        ->and($learner->mobile)->toBe('+919876543210')
        ->and($learner->adult_declared_at)->not->toBeNull()
        ->and($learner->awaitingGuardianConsent())->toBeFalse()
        ->and($user->consents()->sole()->purpose)->toBe(ConsentPurpose::MusicSelf);

    Notification::assertSentOnDemandTimes(GuardianConsentRequest::class, 0);
    $user->markEmailAsVerified();
    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('music.home'));
});

test('MUS-3 an adult must confirm 18+, and an under-18 must give a parent\'s email', function () {
    $this->post(route('register.store'), musicSignUp(['adult' => null]))->assertSessionHasErrors('adult');
    $this->post(route('register.store'), musicSignUp(['age_group' => null, 'adult' => null]))->assertSessionHasErrors('age_group');
    $this->post(route('register.store'), musicSignUp(['age_group' => 'minor', 'adult' => null]))->assertSessionHasErrors('guardian_email');
    $this->post(route('register.store'), musicSignUp(['age_group' => 'minor', 'adult' => null, 'guardian_email' => 'rohan@example.com']))->assertSessionHasErrors('guardian_email');

    expect(User::count())->toBe(0);
});

test('MUS-3 an under-18 learner signs up and their parent is emailed an approval link', function () {
    $this->post(route('register.store'), musicSignUp(['age_group' => 'minor', 'adult' => null, 'guardian_email' => 'mum@example.com', 'marketing' => '1']))
        ->assertSessionHasNoErrors();

    $user = User::where('email', 'rohan@example.com')->sole();
    expect($user->student->awaitingGuardianConsent())->toBeTrue()
        ->and($user->student->guardian_email)->toBe('mum@example.com')
        ->and($user->marketing_opt_in_at)->toBeNull();

    Notification::assertSentOnDemand(GuardianConsentRequest::class, function (GuardianConsentRequest $notification, array $channels, AnonymousNotifiable $notifiable) use ($user) {
        return $notifiable->routes['mail'] === 'mum@example.com' && $notification->learner->is($user->student);
    });
});

test('MUS-3 the parent approves from the signed link, and the consent is stored', function () {
    $learner = Student::factory()->musicLearner(adult: false)->create();
    $url = consentUrl($learner);

    $this->get($url)->assertOk()->assertSee('I agree')->assertSee($learner->user->name);
    $this->post($url, [])->assertSessionHasErrors('confirm');
    expect($learner->fresh()->awaitingGuardianConsent())->toBeTrue();

    $this->post($url, ['confirm' => '1'])->assertOk()->assertSee('the account is approved');

    $learner->refresh();
    expect($learner->awaitingGuardianConsent())->toBeFalse()
        ->and($learner->guardian_consented_at)->not->toBeNull()
        ->and($learner->consents()->where('purpose', ConsentPurpose::MusicGuardian)->exists())->toBeTrue();
});

test('MUS-3 the approval page refuses a link without a valid signature, or for an adult', function () {
    $learner = Student::factory()->musicLearner(adult: false)->create();

    $this->get(route('music.consent.show', $learner))->assertForbidden();
    $this->post(route('music.consent.show', $learner), ['confirm' => '1'])->assertForbidden();
    expect($learner->fresh()->awaitingGuardianConsent())->toBeTrue();

    $adult = Student::factory()->musicLearner()->create();
    $this->get(consentUrl($adult))->assertNotFound();
    $this->get(consentUrl(tuitionChild()))->assertNotFound();
});

test('MUS-2 an approved learner sees open music batches only and asks for a place', function () {
    $admin = User::factory()->admin()->create();
    $learner = Student::factory()->musicLearner()->create();
    $music = Batch::factory()->music()->create(['subject' => 'Guitar beginners (Saturday)']);
    Batch::factory()->create(['subject' => 'Chemistry']);

    Livewire::actingAs($learner->user)->test('pages::portal.music.home')
        ->assertSee('Guitar beginners (Saturday)')
        ->assertSee('₹1,000 a month')
        ->assertDontSee('Chemistry')
        ->call('requestSeat', $music->id)
        ->assertHasNoErrors()
        ->assertSee('Waiting for fee');

    expect(Enrolment::sole()->status)->toBe(EnrolmentStatus::Pending);
    Notification::assertSentTo($admin, EnrolmentRequested::class);
});

test('MUS-2 a music learner cannot join a tuition batch, and a parent cannot put a child in a music batch', function () {
    $learner = Student::factory()->musicLearner()->create();
    $tuition = Batch::factory()->create(['class_level' => 10]);
    $music = Batch::factory()->music()->create();

    Livewire::actingAs($learner->user)->test('pages::portal.music.home')
        ->call('requestSeat', $tuition->id)
        ->assertNotFound();

    $child = tuitionChild(['class_level' => 10]);
    Livewire::actingAs($child->parent->user)->test('pages::portal.parent.classes')
        ->call('requestSeat', $child->id, $music->id)
        ->assertNotFound();

    expect(Enrolment::count())->toBe(0);
});

test('MUS-3 an under-18 learner waits for the parent and cannot join a batch or a class', function () {
    $learner = Student::factory()->musicLearner(adult: false)->create();
    $batch = Batch::factory()->music()->create();

    Livewire::actingAs($learner->user)->test('pages::portal.music.home')
        ->assertSee('Waiting for your parent')
        ->assertDontSee($batch->subject)
        ->call('requestSeat', $batch->id)
        ->assertForbidden();

    // Even if the admin marked the fee by mistake, Join stays closed.
    Enrolment::factory()->for($learner)->for($batch)->active()->create();
    $session = ClassSession::factory()->for($batch)->startingIn(5)->create();
    $this->actingAs($learner->user)->get(route('classes.join', $session))->assertForbidden();

    $learner->forceFill(['guardian_consented_at' => now()])->save();
    $this->actingAs($learner->user->fresh())->get(route('classes.join', $session))->assertRedirect();
});

test('MUS-3 the parent approval email can be sent again, but not too often', function () {
    $learner = Student::factory()->musicLearner(adult: false)->create();

    Livewire::actingAs($learner->user)->test('pages::portal.music.home')
        ->call('resendConsent')
        ->call('resendConsent');

    Notification::assertSentOnDemandTimes(GuardianConsentRequest::class, 1);

    $adult = Student::factory()->musicLearner()->create();
    Livewire::actingAs($adult->user)->test('pages::portal.music.home')->call('resendConsent')->assertForbidden();
});

test('MUS-2 the fee received email goes to the learner and links to their page', function () {
    $learner = Student::factory()->musicLearner()->create();
    $enrolment = Enrolment::factory()->for($learner)->for(Batch::factory()->music())->create();

    app(ActivateEnrolment::class)->handle($enrolment, CarbonImmutable::now()->addMonth());

    Notification::assertSentTo($learner->user, EnrolmentActivated::class, function (EnrolmentActivated $notification) use ($learner) {
        return str_contains($notification->toMail($learner->user)->actionUrl, '/my-music');
    });
});

test('a learner sees and cancels only their own pending request', function () {
    $learner = Student::factory()->musicLearner()->create();
    $other = Student::factory()->musicLearner()->create();
    $otherEnrolment = Enrolment::factory()->for($other)->for(Batch::factory()->music())->create();

    Livewire::actingAs($learner->user)->test('pages::portal.music.home')
        ->call('cancelRequest', $otherEnrolment->id)
        ->assertForbidden();

    expect($otherEnrolment->fresh())->not->toBeNull();
});

test('CHAT-4 an adult learner can message us, an under-18 learner cannot', function () {
    $adult = Student::factory()->musicLearner()->create();
    $minor = Student::factory()->musicLearner(adult: false)->create();
    $minor->forceFill(['guardian_consented_at' => now()])->save();

    Livewire::actingAs($adult->user)->test('pages::portal.messages.index')
        ->set('subject', 'Strings')->set('body', 'Which strings should I buy?')
        ->call('start')->assertHasNoErrors()->assertRedirect();

    $conversation = Conversation::sole();
    expect($conversation->offering)->toBe(Offering::Music)
        ->and($conversation->student_id)->toBe($adult->id)
        ->and($conversation->members()->pluck('users.id')->all())->toBe([$adult->user_id]);

    Livewire::actingAs($minor->user)->test('pages::portal.messages.index')
        ->set('subject', 'Hi')->set('body', 'Hello')
        ->call('start')->assertForbidden();
});

test('NOT-2 music learners see notices for music learners and their batch, not parents\' notices', function () {
    $learner = Student::factory()->musicLearner()->create();
    $batch = Batch::factory()->music()->create();
    Enrolment::factory()->for($learner)->for($batch)->active()->create();

    $make = fn (NoticeAudience $audience, string $title, ?int $batchId = null) => tap(new Notice(['audience' => $audience, 'title' => $title, 'body' => 'Body', 'batch_id' => $batchId]))
        ->forceFill(['published_at' => now()])->save();
    $make(NoticeAudience::MusicLearners, 'Music recital');
    $make(NoticeAudience::Batch, 'Guitar class moved', $batch->id);
    $make(NoticeAudience::Parents, 'Exam week');

    expect(Notice::query()->for($learner->user)->pluck('title')->sort()->values()->all())->toBe(['Guitar class moved', 'Music recital']);

    $notice = Notice::query()->where('title', 'Guitar class moved')->sole();
    expect($notice->recipients()->pluck('id')->all())->toBe([$learner->user_id]);
});

test('MUS-3 under-18 accounts not approved within 14 days are deleted', function () {
    $old = Student::factory()->musicLearner(adult: false)->create();
    $old->user->forceFill(['created_at' => now()->subDays(15)])->save();
    $recent = Student::factory()->musicLearner(adult: false)->create();
    $approved = Student::factory()->musicLearner(adult: false)->create(['guardian_consented_at' => now()]);
    $approved->user->forceFill(['created_at' => now()->subDays(30)])->save();

    $this->artisan('app:prune-unapproved-music-learners')->assertSuccessful();

    expect(User::find($old->user_id))->toBeNull()
        ->and(Student::find($old->id))->toBeNull()
        ->and(Student::find($recent->id))->not->toBeNull()
        ->and(Student::find($approved->id))->not->toBeNull();
});
