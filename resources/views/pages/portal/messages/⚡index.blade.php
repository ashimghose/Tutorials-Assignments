<?php

use App\Actions\Chat\PostMessage;
use App\Actions\Chat\StartConversation;
use App\Enums\Offering;
use App\Models\AcademicOrder;
use App\Models\Batch;
use App\Models\Conversation;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
| CHAT-1, CHAT-2: the conversations a person can see, with unread counts,
| and (for parents, school students and academic students) a new message.
*/
new #[Title('Messages')] class extends Component {
    use WithFileUploads;

    public string $subject = '';

    public string $body = '';

    public ?int $student_id = null;

    public ?int $batch_id = null;

    public ?int $order_id = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $attachment = null;

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        $user = Auth::user();

        return Conversation::query()->visibleTo($user)->withUnreadFor($user)
            ->with('starter', 'student', 'assignee')
            ->orderByRaw('closed_at is not null')
            ->latest('last_message_at')->latest('id')
            ->get();
    }

    /**
     * A parent's children, for "which child is it about?".
     *
     * @return Collection<int, Student>
     */
    #[Computed]
    public function children(): Collection
    {
        return Auth::user()->parentProfile?->students()->orderBy('first_name')->get() ?? collect();
    }

    /**
     * Batches the conversation can be about: the chosen child's (or the student's own).
     *
     * @return Collection<int, Batch>
     */
    #[Computed]
    public function batches(): Collection
    {
        $user = Auth::user();
        $student = $user->student ?? $this->children->firstWhere('id', $this->student_id);

        return $student === null ? collect() : Batch::query()
            ->whereIn('id', $student->enrolments()->select('batch_id'))
            ->orderBy('subject')->get();
    }

    /**
     * @return Collection<int, AcademicOrder>
     */
    #[Computed]
    public function orders(): Collection
    {
        $student = Auth::user()->academicStudent;

        return $student === null ? collect() : AcademicOrder::query()->whereBelongsTo($student)->latest('id')->get();
    }

    public function updatedStudentId(): void
    {
        $this->batch_id = null;
        unset($this->batches);
    }

    public function start(StartConversation $start): void
    {
        $user = Auth::user();
        $this->authorize('create', Conversation::class);

        $this->validate([
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            // Only your own child, their batches, and your own orders (checked on the server).
            'student_id' => ['nullable', 'integer', Rule::in($this->children->pluck('id')->all())],
            'batch_id' => ['nullable', 'integer', Rule::in($this->batches->pluck('id')->all())],
            'order_id' => ['nullable', 'integer', Rule::in($this->orders->pluck('id')->all())],
            'attachment' => PostMessage::attachmentRules(),
        ], ['attachment.mimetypes' => __('Attach an image, PDF or Word (.docx) file.')]);
        PostMessage::ensureNotTooFast($user);

        $conversation = $start->handle(
            $user,
            $user->academicStudent !== null ? Offering::Academic : Offering::Tuition,
            $this->subject,
            $this->body,
            student: $this->children->firstWhere('id', $this->student_id),
            batch: $this->batches->firstWhere('id', $this->batch_id),
            order: $this->orders->firstWhere('id', $this->order_id),
            attachment: $this->attachment,
        );

        $this->redirectRoute('messages.show', $conversation, navigate: true);
    }
}; ?>

<section class="flex w-full max-w-4xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Messages') }}</flux:heading>
            <flux:subheading>
                @if (auth()->user()->isAdmin())
                    {{ __('Every conversation. Open one to reply, assign it or close it.') }}
                @elseif (auth()->user()->teacher)
                    {{ __('Conversations assigned to you and about your batches.') }}
                @else
                    {{ __('Ask us anything. We reply during office hours: :hours.', ['hours' => \App\Support\OfficeHours::summary()]) }}
                @endif
            </flux:subheading>
        </div>
        @can('create', \App\Models\Conversation::class)
            <flux:modal.trigger name="new-message">
                <flux:button variant="primary" icon="plus" data-test="new-message">{{ __('New message') }}</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    <div class="space-y-2" wire:poll.30s>
        @forelse ($this->conversations as $conversation)
            <a href="{{ route('messages.show', $conversation) }}" wire:navigate wire:key="conversation-{{ $conversation->id }}"
               class="block rounded-xl border border-zinc-200 bg-white p-4 hover:border-zinc-300 {{ $conversation->isClosed() ? 'opacity-70' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-zinc-900">{{ $conversation->subject }}</p>
                        <p class="text-sm text-zinc-600">
                            {{ $conversation->offering->label() }}
                            · {{ $conversation->starter?->name ?? __('Deleted account') }}
                            @if ($conversation->student)
                                · {{ __('about :name', ['name' => $conversation->student->first_name]) }}
                            @endif
                            @if ($conversation->last_message_at)
                                · {{ $conversation->last_message_at->setTimezone(config('app.display_timezone'))->format('j M, g:i a') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($conversation->isClosed())
                            <flux:badge size="sm">{{ __('Closed') }}</flux:badge>
                        @endif
                        @if (auth()->user()->isAdmin() && $conversation->assignee)
                            <flux:badge size="sm" color="sky">{{ $conversation->assignee->name }}</flux:badge>
                        @endif
                        @if ($conversation->unread_count > 0)
                            <flux:badge size="sm" color="amber" data-test="unread">{{ trans_choice(':count new|:count new', $conversation->unread_count) }}</flux:badge>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <flux:card class="text-center">
                <flux:heading>{{ __('No messages yet') }}</flux:heading>
                @can('create', \App\Models\Conversation::class)
                    <flux:text class="mt-1">{{ __('Questions about classes, fees or an order? Send us a message.') }}</flux:text>
                @endcan
            </flux:card>
        @endforelse
    </div>

    @can('create', \App\Models\Conversation::class)
        <flux:modal name="new-message" class="w-full max-w-lg">
            <form wire:submit="start" class="space-y-4">
                <flux:heading size="lg">{{ __('New message') }}</flux:heading>

                @if ($this->children->isNotEmpty())
                    <flux:select wire:model.live="student_id" :label="__('About which child?')" :placeholder="__('Not about one child')">
                        @foreach ($this->children as $child)
                            <flux:select.option :value="$child->id">{{ $child->first_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                @if ($this->batches->isNotEmpty())
                    <flux:select wire:model="batch_id" :label="__('About a class? (optional)')" :placeholder="__('No particular class')">
                        @foreach ($this->batches as $batch)
                            <flux:select.option :value="$batch->id">{{ $batch->name() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                @if ($this->orders->isNotEmpty())
                    <flux:select wire:model="order_id" :label="__('About an order? (optional)')" :placeholder="__('No particular order')">
                        @foreach ($this->orders as $order)
                            <flux:select.option :value="$order->id">{{ $order->reference() }} · {{ $order->service_title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:input wire:model="subject" :label="__('Subject')" maxlength="160" required />
                <flux:textarea wire:model="body" :label="__('Message')" rows="5" required />

                <flux:field>
                    <flux:label>{{ __('Attach a file (optional)') }}</flux:label>
                    <input type="file" wire:model="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx" aria-label="{{ __('Attach a file') }}" class="block w-full text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:font-medium">
                    <flux:description>{{ __('Image, PDF or Word, up to 10 MB.') }}</flux:description>
                    <flux:error name="attachment" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="filled">{{ __('Cancel') }}</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" data-test="send-new-message">{{ __('Send') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</section>
