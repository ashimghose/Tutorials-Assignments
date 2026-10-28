<?php

use App\Enums\MarketingAudience;
use App\Enums\NoticeAudience;
use App\Filament\Resources\MarketingEmails\Pages\ListMarketingEmails;
use App\Filament\Resources\Notices\Pages\ListNotices;
use App\Mail\MarketingMail;
use App\Models\Batch;
use App\Models\Enrolment;
use App\Models\Lead;
use App\Models\MarketingEmail;
use App\Models\Notice;
use App\Models\User;
use App\Notifications\NoticePublished;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Mail::fake();
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('NOT-2 a notice to one batch reaches only that batch\'s parents, by email and on their page', function () {
    $batch = Batch::factory()->create();
    $inBatch = tuitionChild();
    Enrolment::factory()->active()->create(['student_id' => $inBatch->id, 'batch_id' => $batch->id]);
    $elsewhere = tuitionChild();
    $academic = User::factory()->academicStudent()->create();

    Livewire::test(ListNotices::class)
        ->callAction(TestAction::make('create')->table(), data: ['audience' => NoticeAudience::Batch->value, 'batch_id' => $batch->id, 'title' => 'No class on Diwali', 'body' => 'Classes resume on Monday.'])
        ->assertHasNoActionErrors();
    $notice = Notice::sole();
    expect($notice->published_at)->toBeNull();

    // Drafts aren't shown.
    Livewire::actingAs($inBatch->parent->user)->test('pages::portal.parent.children')->assertDontSee('No class on Diwali');

    Livewire::actingAs($this->admin)->test(ListNotices::class)->callTableAction('publish', $notice);

    expect($notice->fresh()->published_at)->not->toBeNull();
    Notification::assertSentTo($inBatch->parent->user, NoticePublished::class);
    Notification::assertNotSentTo([$elsewhere->parent->user, $academic], NoticePublished::class);

    Livewire::actingAs($inBatch->parent->user)->test('pages::portal.parent.children')->assertSee('No class on Diwali');
    Livewire::actingAs($elsewhere->parent->user)->test('pages::portal.parent.children')->assertDontSee('No class on Diwali');
});

test('NOT-2 a notice to academic students shows on their orders page', function () {
    $student = User::factory()->academicStudent()->create();
    $notice = Notice::create(['audience' => NoticeAudience::AcademicStudents, 'title' => 'Office closed on Friday', 'body' => 'Orders continue as usual.']);

    Livewire::test(ListNotices::class)->callTableAction('publish', $notice);

    Notification::assertSentTo($student, NoticePublished::class);
    Livewire::actingAs($student)->test('pages::portal.academic.orders')->assertSee('Office closed on Friday');
});

test('a published notice cannot be edited or published again', function () {
    $notice = Notice::create(['audience' => NoticeAudience::Parents, 'title' => 'Hello', 'body' => 'Welcome back.']);
    $notice->forceFill(['published_at' => now()])->save();

    Livewire::test(ListNotices::class)
        ->assertTableActionHidden('publish', $notice)
        ->assertTableActionHidden('edit', $notice);
    expect($this->admin->can('publish', $notice))->toBeFalse();
});

test('NOT-3 a marketing email goes only to people who opted in, with a one-click unsubscribe', function () {
    $optedIn = User::factory()->parent()->create(['email' => 'yes@example.com']);
    $optedIn->forceFill(['marketing_opt_in_at' => now()])->save();
    User::factory()->parent()->create(['email' => 'no@example.com']);
    $leadYes = Lead::factory()->create(['email' => 'lead-yes@example.com', 'marketing_consent' => true]);
    Lead::factory()->create(['email' => 'lead-no@example.com', 'marketing_consent' => false]);

    $email = MarketingEmail::create(['audience' => MarketingAudience::Parents, 'subject' => 'New Class 10 batch', 'body' => 'Seats open now.']);
    Livewire::test(ListMarketingEmails::class)->callTableAction('send', $email);

    Mail::assertQueued(MarketingMail::class, 1);
    Mail::assertQueued(MarketingMail::class, function (MarketingMail $mail) use ($optedIn) {
        $headers = $mail->headers()->text;

        return $mail->hasTo('yes@example.com')
            && $headers['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click'
            && str_contains($headers['List-Unsubscribe'], "/unsubscribe/user/{$optedIn->id}");
    });
    expect($email->fresh()->recipients)->toBe(1)
        ->and($this->admin->can('send', $email->fresh()))->toBeFalse();

    $leadsEmail = MarketingEmail::create(['audience' => MarketingAudience::TuitionLeads, 'subject' => 'Free demo week', 'body' => 'Book a demo.']);
    Livewire::test(ListMarketingEmails::class)->callTableAction('send', $leadsEmail);
    Mail::assertQueued(MarketingMail::class, fn (MarketingMail $mail) => $mail->hasTo('lead-yes@example.com'));
    Mail::assertNotQueued(MarketingMail::class, fn (MarketingMail $mail) => $mail->hasTo('lead-no@example.com') || $mail->hasTo('no@example.com'));
});

test('NOT-3 the unsubscribe link works in one click and cannot be forged', function () {
    $user = User::factory()->parent()->create();
    $user->forceFill(['marketing_opt_in_at' => now()])->save();
    $lead = Lead::factory()->create(['marketing_consent' => true]);

    $this->get(URL::signedRoute('unsubscribe', ['type' => 'user', 'id' => $user->id]))->assertOk()->assertSee('unsubscribed');
    expect($user->fresh()->marketing_opt_in_at)->toBeNull();

    // The mail app's own button posts without a CSRF token.
    $this->post(URL::signedRoute('unsubscribe', ['type' => 'lead', 'id' => $lead->id]))->assertNoContent();
    expect($lead->fresh()->marketing_consent)->toBeFalse();

    $other = User::factory()->parent()->create();
    $other->forceFill(['marketing_opt_in_at' => now()])->save();
    $this->get(route('unsubscribe', ['type' => 'user', 'id' => $other->id]))->assertForbidden();
    expect($other->fresh()->marketing_opt_in_at)->not->toBeNull();
});

test('NOT-3 sign-up asks for marketing consent, unticked by default', function (bool $ticked) {
    auth()->logout();
    $this->post(route('register.store'), [
        'account_type' => 'parent',
        'name' => 'Asha Sen',
        'email' => 'asha@example.com',
        'mobile' => '9876543210',
        'password' => 'password',
        'password_confirmation' => 'password',
        'consent' => '1',
        ...($ticked ? ['marketing' => '1'] : []),
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'asha@example.com')->sole()->marketing_opt_in_at !== null)->toBe($ticked);
})->with(['opted in' => true, 'not ticked' => false]);

test('only admins reach notices and marketing emails', function (string $path) {
    $this->actingAs(User::factory()->parent()->create())->get($path)->assertForbidden();
    $this->actingAs($this->admin)->get($path)->assertOk();
})->with(['/admin/notices', '/admin/marketing-emails']);
