<?php

use App\Actions\Chat\PostMessage;
use App\Actions\Chat\StartConversation;
use App\Enums\Offering;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Jobs\EmailUnreadMessage;
use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Conversation;
use App\Models\Enrolment;
use App\Models\Message;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\NewConversation;
use App\Notifications\UnreadMessage;
use App\Notifications\Welcome;
use Carbon\CarbonImmutable;
use Database\Seeders\AcademicCatalogueSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    // Office open all week unless a test says otherwise.
    config(['chat.office_hours' => ['days' => [1, 2, 3, 4, 5, 6, 7], 'opens' => '00:00', 'closes' => '23:59']]);

    $this->admin = User::factory()->admin()->create();
    $this->child = tuitionChild(['class_level' => 10]);
    $this->parent = $this->child->parent->user;
    $this->batch = Batch::factory()->create(['class_level' => 10]);
    Enrolment::factory()->active()->create(['student_id' => $this->child->id, 'batch_id' => $this->batch->id]);
});

function startChat(User $user, array $values = [])
{
    $component = Livewire::actingAs($user)->test('pages::portal.messages.index');
    foreach (['subject' => 'Question about homework', 'body' => 'Can we go over chapter 3 again?', ...$values] as $field => $value) {
        $component->set($field, $value);
    }

    return $component->call('start');
}

test('CHAT-4 a school student\'s conversation always includes their parent', function () {
    $studentUser = $this->child->user;

    startChat($studentUser, ['batch_id' => $this->batch->id])->assertHasNoErrors()->assertRedirect();

    $conversation = Conversation::sole();
    expect($conversation->offering)->toBe(Offering::Tuition)
        ->and($conversation->student_id)->toBe($this->child->id)
        ->and($conversation->members()->pluck('users.id')->sort()->values()->all())->toBe(collect([$studentUser->id, $this->parent->id])->sort()->values()->all());
    Notification::assertSentTo($this->admin, NewConversation::class);

    // The parent sees it and can reply; another parent can't open it.
    Livewire::actingAs($this->parent)->test('pages::portal.messages.index')->assertSee('Question about homework');
    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->assertSee('Can we go over chapter 3 again?')
        ->set('body', 'Thanks, I will check with the teacher.')
        ->call('send')
        ->assertHasNoErrors();

    $stranger = tuitionChild()->parent->user;
    Livewire::actingAs($stranger)->test('pages::portal.messages.show', ['conversation' => $conversation])->assertForbidden();
    Livewire::actingAs($stranger)->test('pages::portal.messages.index')->assertDontSee('Question about homework');
});

test('CHAT-2 the batch teacher sees conversations about their batch; other teachers do not', function () {
    startChat($this->parent, ['student_id' => $this->child->id, 'batch_id' => $this->batch->id])->assertHasNoErrors();
    $conversation = Conversation::sole();

    $teacher = $this->batch->teacher->user;
    Livewire::actingAs($teacher)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->set('body', 'Yes, we will revise it on Monday.')
        ->call('send')
        ->assertHasNoErrors();

    $other = Teacher::factory()->create()->user;
    Livewire::actingAs($other)->test('pages::portal.messages.show', ['conversation' => $conversation])->assertForbidden();
    Livewire::actingAs($other)->test('pages::portal.messages.index')->assertDontSee($conversation->subject);
});

test('a parent cannot start a conversation about someone else\'s child or a batch their child is not in', function () {
    $stranger = tuitionChild(['class_level' => 10]);
    $otherBatch = Batch::factory()->create(['class_level' => 10]);

    startChat($this->parent, ['student_id' => $stranger->id])->assertHasErrors('student_id');
    startChat($this->parent, ['student_id' => $this->child->id, 'batch_id' => $otherBatch->id])->assertHasErrors('batch_id');

    expect(Conversation::count())->toBe(0);
});

test('CHAT-4 teachers and admins cannot start conversations, so there are no private teacher-to-minor chats', function (string $role) {
    $user = $role === 'teacher' ? $this->batch->teacher->user : $this->admin;

    startChat($user)->assertForbidden();
    expect(Conversation::count())->toBe(0);
})->with(['teacher', 'admin']);

test('M7 an academic order conversation reaches the assigned expert, and only them', function () {
    $this->seed(AcademicCatalogueSeeder::class);
    $expert = Teacher::factory()->create(['offering' => Offering::Academic]);
    $order = AcademicOrder::factory()->paid()->create(['expert_id' => $expert->id]);
    $student = $order->academicStudent->user;

    startChat($student, ['order_id' => $order->id, 'subject' => 'Chapter 2 comments'])->assertHasNoErrors();
    $conversation = Conversation::sole();
    expect($conversation->offering)->toBe(Offering::Academic)
        ->and($conversation->assigned_to)->toBe($expert->user_id);

    Livewire::actingAs($expert->user)->test('pages::portal.messages.index')->assertSee('Chapter 2 comments');
    Livewire::actingAs($expert->user)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->set('body', 'I have added comments to chapter 2.')
        ->call('send')
        ->assertHasNoErrors();

    $otherExpert = Teacher::factory()->create(['offering' => Offering::Academic])->user;
    Livewire::actingAs($otherExpert)->test('pages::portal.messages.show', ['conversation' => $conversation])->assertForbidden();

    // Someone else's order can't be linked.
    $otherOrder = AcademicOrder::factory()->create();
    startChat($student, ['order_id' => $otherOrder->id])->assertHasErrors('order_id');
});

test('CHAT-2 CHAT-3 unread counts and read receipts', function () {
    startChat($this->parent, ['student_id' => $this->child->id])->assertHasNoErrors();
    $conversation = Conversation::sole();

    Livewire::actingAs($this->admin)->test('pages::portal.messages.index')->assertSee('1 new');

    // The parent's message isn't seen until the admin opens it.
    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation])->assertDontSee('Seen');

    Livewire::actingAs($this->admin)->test('pages::portal.messages.show', ['conversation' => $conversation]);
    Livewire::actingAs($this->admin)->test('pages::portal.messages.index')->assertDontSee('1 new');
    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation])->assertSee('Seen');
});

test('NOT-1 a message unread for 15 minutes is emailed once, without quoting it', function () {
    Queue::fake();
    startChat($this->parent, ['student_id' => $this->child->id])->assertHasNoErrors();
    $conversation = Conversation::sole();
    Queue::assertPushed(EmailUnreadMessage::class);

    $teacher = $this->batch->teacher->user;
    $conversation->forceFill(['assigned_to' => $teacher->id])->save();
    $this->travel(1)->minutes();
    $reply = app(PostMessage::class)->handle($conversation, $this->admin, 'Private details about fees');

    (new EmailUnreadMessage($reply))->handle();
    (new EmailUnreadMessage($reply))->handle();

    Notification::assertSentToTimes($this->parent, UnreadMessage::class, 1);
    Notification::assertSentToTimes($teacher, UnreadMessage::class, 1);
    Notification::assertNotSentTo($this->admin, UnreadMessage::class);
    Notification::assertSentTo($this->parent, UnreadMessage::class, function (UnreadMessage $notification) {
        return ! str_contains(implode(' ', $notification->toMail($this->parent)->introLines), 'Private details');
    });

    // Once read, the next message isn't emailed to the reader.
    $conversation->markReadBy($this->parent);
    $this->travel(1)->minutes();
    (new EmailUnreadMessage($reply))->handle();
    Notification::assertSentToTimes($this->parent, UnreadMessage::class, 1);
});

test('CHAT-3 attachments are checked, private, and downloaded through a signed link', function () {
    $pdf = UploadedFile::fake()->createWithContent('timetable.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
    startChat($this->parent, ['student_id' => $this->child->id, 'attachment' => $pdf])->assertHasNoErrors();
    $message = Message::sole();
    Storage::disk('local')->assertExists($message->attachment_path);

    $link = URL::temporarySignedRoute('messages.attachment', now()->addMinutes(10), ['message' => $message]);
    $this->actingAs($this->parent)->get($link)->assertOk()->assertDownload('timetable.pdf');
    $this->actingAs(tuitionChild()->parent->user)->get($link)->assertForbidden();
    $this->actingAs($this->parent)->get(route('messages.attachment', $message))->assertForbidden();

    $exe = UploadedFile::fake()->create('virus.exe', 10, 'application/x-dosexec');
    startChat($this->parent, ['attachment' => $exe])->assertHasErrors('attachment');
});

test('CHAT-3 people in the conversation can report a message to the admins', function () {
    startChat($this->child->user)->assertHasNoErrors();
    $conversation = Conversation::sole();
    $message = $conversation->messages()->sole();

    // Not your own message.
    Livewire::actingAs($this->child->user)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->set('reporting', $message->id)->set('report_reason', 'Test')->call('report')->assertForbidden();

    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->call('startReport', $message->id)
        ->set('report_reason', 'Rude language')
        ->call('report')
        ->assertHasNoErrors();

    expect($message->fresh()->reported_by)->toBe($this->parent->id);
    Livewire::actingAs($this->admin)->test('pages::portal.messages.show', ['conversation' => $conversation])->assertSee('Reported: Rude language');
    Livewire::actingAs($this->admin)->test(ListConversations::class)->assertCanSeeTableRecords([$conversation]);
});

test('CHAT-2 only admins assign and close conversations; nobody can reply to a closed one', function () {
    startChat($this->parent)->assertHasNoErrors();
    $conversation = Conversation::sole();
    $teacher = $this->batch->teacher->user;

    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->call('toggleClosed')->assertForbidden();

    Livewire::actingAs($this->admin)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->set('assign_to', $this->parent->id)->call('assign')->assertHasErrors('assign_to')
        ->set('assign_to', $teacher->id)->call('assign')->assertHasNoErrors()
        ->call('toggleClosed');

    expect($conversation->fresh()->assigned_to)->toBe($teacher->id)
        ->and($conversation->fresh()->isClosed())->toBeTrue()
        ->and(AuditLog::where('action', 'conversation.closed')->exists())->toBeTrue();

    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation->fresh()])
        ->assertSee('This conversation is closed')
        ->set('body', 'One more thing')->call('send')->assertForbidden();
});

test('CHAT-5 outside office hours the chat says when we will reply, once', function () {
    config(['chat.office_hours' => ['days' => [1, 2, 3, 4, 5, 6], 'opens' => '10:00', 'closes' => '19:00']]);
    $this->travelTo(CarbonImmutable::parse('2026-11-01 12:00', 'Asia/Kolkata')); // a Sunday

    startChat($this->parent)->assertHasNoErrors();
    $conversation = Conversation::sole();
    app(PostMessage::class)->handle($conversation, $this->parent, 'Another question');

    $automatic = $conversation->messages()->whereNull('sender_id')->get();
    expect($automatic)->toHaveCount(1)
        ->and($automatic->first()->body)->toContain('Monday, 10:00 am');

    Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => $conversation])
        ->assertSee('Our office is closed now');
});

test('messages are rate-limited', function () {
    startChat($this->parent)->assertHasNoErrors();
    $component = Livewire::actingAs($this->parent)->test('pages::portal.messages.show', ['conversation' => Conversation::sole()]);

    foreach (range(1, 9) as $i) {
        $component->set('body', "Message {$i}")->call('send')->assertHasNoErrors();
    }
    $component->set('body', 'One too many')->call('send')->assertHasErrors('body');
});

test('NOT-1 parents and academic students get a welcome email once verified', function () {
    $student = User::factory()->academicStudent()->create();
    $teacher = Teacher::factory()->create()->user;

    event(new Verified($this->parent));
    event(new Verified($student));
    event(new Verified($teacher));

    Notification::assertSentTo([$this->parent, $student], Welcome::class);
    Notification::assertNotSentTo($teacher, Welcome::class);
});

test('only admins reach the inbox in the admin panel', function () {
    $this->actingAs($this->parent)->get('/admin/conversations')->assertForbidden();
    $this->actingAs($this->admin)->get('/admin/conversations')->assertOk();
});

test('messages are off until the chat module is switched on', function () {
    config(['features.chat' => false]);

    $this->actingAs($this->parent)->get('/messages')->assertNotFound();
});

test('StartConversation adds the parent even when a parent starts it about their child', function () {
    $conversation = app(StartConversation::class)->handle($this->parent, Offering::Tuition, 'Fees', 'When is the fee due?', student: $this->child);

    expect($conversation->members()->count())->toBe(1)
        ->and($conversation->members()->first()->id)->toBe($this->parent->id);
});
