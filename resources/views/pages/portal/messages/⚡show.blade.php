<?php

use App\Actions\Chat\PostMessage;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
| CHAT-2 to CHAT-5: one conversation. New messages appear by polling every
| few seconds (no paid real-time service), opening it marks it read, and
| the sender sees "Seen" once someone else has read their message.
*/
new #[Title('Messages')] class extends Component {
    use WithFileUploads;

    public Conversation $conversation;

    public string $body = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $attachment = null;

    public ?int $reporting = null;

    public string $report_reason = '';

    public ?int $assign_to = null;

    public function mount(Conversation $conversation): void
    {
        $this->authorize('view', $conversation);
        $this->conversation = $conversation;
        $this->assign_to = $conversation->assigned_to;
    }

    /**
     * @return Collection<int, Message>
     */
    #[Computed]
    public function messages(): Collection
    {
        // Reading the page marks it read (CHAT-3 read receipts, NOT-1 unread emails).
        $this->conversation->markReadBy(Auth::user());

        return $this->conversation->messages()->with('sender')->get();
    }

    /**
     * CHAT-2: teachers and experts the admin can assign the conversation to.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function staff(): Collection
    {
        return User::query()->where('role', Role::Teacher)->where('status', 'active')->orderBy('name')->get();
    }

    public function send(PostMessage $post): void
    {
        $this->authorize('reply', $this->conversation);
        $this->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => PostMessage::attachmentRules(),
        ], ['attachment.mimetypes' => __('Attach an image, PDF or Word (.docx) file.')]);
        PostMessage::ensureNotTooFast(Auth::user());

        $post->handle($this->conversation, Auth::user(), $this->body, $this->attachment);

        $this->reset('body', 'attachment');
        unset($this->messages);
    }

    public function startReport(int $messageId): void
    {
        $this->reporting = $messageId;
        $this->report_reason = '';
        Flux::modal('report')->show();
    }

    /**
     * CHAT-3: anyone in the conversation can report a message to the admins.
     */
    public function report(): void
    {
        $message = $this->conversation->messages()->findOrFail((int) $this->reporting);
        $this->authorize('report', $message);
        $this->validate(['report_reason' => ['required', 'string', 'max:255']]);

        $message->forceFill(['reported_at' => now(), 'reported_by' => Auth::id(), 'report_reason' => $this->report_reason])->save();

        $this->reset('reporting', 'report_reason');
        unset($this->messages);
        Flux::modal('report')->close();
        Flux::toast(text: __('Thank you. The admin team will look at it.'));
    }

    public function assign(): void
    {
        $this->authorize('manage', $this->conversation);
        $this->validate(['assign_to' => ['nullable', 'integer', Rule::in($this->staff->pluck('id')->all())]]);

        $this->conversation->forceFill(['assigned_to' => $this->assign_to])->save();
        AuditLog::record('conversation.assigned', $this->conversation, ['assigned_to' => $this->assign_to]);
        Flux::toast(variant: 'success', text: __('Assignment saved.'));
    }

    public function toggleClosed(): void
    {
        $this->authorize('manage', $this->conversation);

        $closing = ! $this->conversation->isClosed();
        $this->conversation->forceFill(['closed_at' => $closing ? now() : null])->save();
        AuditLog::record($closing ? 'conversation.closed' : 'conversation.reopened', $this->conversation);
    }
}; ?>

@php($me = auth()->user())
@php($lastOwn = $this->messages->where('sender_id', $me->id)->last())
@php($tz = config('app.display_timezone'))
<section class="flex w-full max-w-3xl flex-col gap-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <flux:text><a href="{{ route('messages.index') }}" wire:navigate class="hover:underline">{{ __('Messages') }}</a></flux:text>
            <flux:heading size="xl" level="1" class="break-words">{{ $conversation->subject }}</flux:heading>
            <flux:subheading>
                {{ $conversation->offering->label() }}
                @if ($conversation->student) · {{ __('about :name', ['name' => $conversation->student->first_name]) }} @endif
                @if ($conversation->batch) · {{ $conversation->batch->name() }} @endif
                @if ($conversation->order) · {{ $conversation->order->reference() }} @endif
            </flux:subheading>
        </div>
        @if ($conversation->isClosed())
            <flux:badge>{{ __('Closed') }}</flux:badge>
        @endif
    </div>

    @unless ($me->isAdmin() || $me->teacher || \App\Support\OfficeHours::isOpen())
        <flux:callout icon="clock" color="zinc" data-test="office-hours">
            <flux:callout.text>
                {{ __('Our office is closed now. Office hours: :hours. We will reply by :time.', [
                    'hours' => \App\Support\OfficeHours::summary(),
                    'time' => \App\Support\OfficeHours::nextOpening()->format('l, g:i a'),
                ]) }}
            </flux:callout.text>
        </flux:callout>
    @endunless

    @can('manage', $conversation)
        <flux:card class="flex flex-wrap items-end gap-3">
            <form wire:submit="assign" class="flex flex-wrap items-end gap-3">
                <flux:select wire:model="assign_to" :label="__('Assigned to')" :placeholder="__('Admin team only')" class="min-w-56">
                    @foreach ($this->staff as $person)
                        <flux:select.option :value="$person->id">{{ $person->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button type="submit" size="sm">{{ __('Save') }}</flux:button>
            </form>
            <flux:button size="sm" variant="filled" wire:click="toggleClosed" class="ms-auto">
                {{ $conversation->isClosed() ? __('Reopen') : __('Close conversation') }}
            </flux:button>
        </flux:card>
    @endcan

    <div class="space-y-3" wire:poll.5s data-test="messages">
        @foreach ($this->messages as $message)
            @php($mine = $message->sender_id === $me->id)
            <div wire:key="message-{{ $message->id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $mine ? 'bg-brand-50 text-zinc-900' : ($message->isAutomatic() ? 'bg-zinc-50 text-zinc-600 italic' : 'bg-white border border-zinc-200 text-zinc-900') }}">
                    <p class="text-xs font-medium text-zinc-500">
                        {{ $message->isAutomatic() ? __('Automatic reply') : ($mine ? __('You') : ($message->sender?->name ?? __('Deleted account'))) }}
                        · {{ $message->created_at->setTimezone($tz)->format('j M, g:i a') }}
                    </p>
                    <p class="mt-1 whitespace-pre-line break-words">{{ $message->body }}</p>
                    @if ($message->attachment_path)
                        <a href="{{ URL::temporarySignedRoute('messages.attachment', now()->addMinutes(10), ['message' => $message]) }}" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline">
                            <flux:icon.paper-clip variant="micro" /> {{ $message->attachment_name }}
                        </a>
                    @endif
                    <div class="mt-1 flex items-center gap-3 text-xs text-zinc-500">
                        @if ($mine && $lastOwn?->is($message) && $conversation->isSeen($message))
                            <span data-test="seen">{{ __('Seen') }}</span>
                        @endif
                        @if ($message->reported_at && $me->isAdmin())
                            <span class="font-medium text-red-700">{{ __('Reported: :reason', ['reason' => $message->report_reason]) }}</span>
                        @elseif ($message->reported_at && $message->reported_by === $me->id)
                            <span>{{ __('Reported') }}</span>
                        @endif
                        @can('report', $message)
                            <button type="button" wire:click="startReport({{ $message->id }})" class="hover:underline">{{ __('Report') }}</button>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @can('reply', $conversation)
        <form wire:submit="send" class="space-y-2 rounded-xl border border-zinc-200 bg-white p-3">
            <flux:textarea wire:model="body" :placeholder="__('Write a message')" rows="3" :aria-label="__('Message')" required />
            <flux:error name="body" />
            <div class="flex flex-wrap items-center justify-between gap-2">
                <input type="file" wire:model="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx" aria-label="{{ __('Attach a file') }}" class="block max-w-full text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:font-medium">
                <flux:button type="submit" variant="primary" icon="paper-airplane" data-test="send-message">{{ __('Send') }}</flux:button>
            </div>
            <flux:error name="attachment" />
        </form>
    @elseif ($conversation->isClosed())
        <flux:text>{{ __('This conversation is closed. Start a new message if you need anything else.') }}</flux:text>
    @endcan

    <flux:modal name="report" class="w-full max-w-md">
        <form wire:submit="report" class="space-y-4">
            <flux:heading size="lg">{{ __('Report this message') }}</flux:heading>
            <flux:text>{{ __('Tell us what is wrong. Only the admin team sees reports.') }}</flux:text>
            <flux:textarea wire:model="report_reason" :label="__('Reason')" rows="3" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="filled">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('Report') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
