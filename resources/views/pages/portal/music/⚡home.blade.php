<?php

use App\Actions\Enrolments\RequestEnrolment;
use App\Actions\Music\SendGuardianConsentRequest;
use App\Enums\EnrolmentStatus;
use App\Enums\Offering;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\Student;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| MUS-2, MUS-3: a music learner's own page. They see their batches, fee
| status and timetable, and ask for a place in a music batch, which waits for
| the fee like tuition. An under-18 learner waits for their parent's approval.
*/
new #[Title('My music classes')] class extends Component {
    #[Computed]
    public function learner(): Student
    {
        return Auth::user()->student()->firstOrFail();
    }

    /**
     * @return Collection<int, Enrolment>
     */
    #[Computed]
    public function enrolments(): Collection
    {
        return $this->learner->enrolments()
            ->where('status', '!=', EnrolmentStatus::Removed)
            ->with('batch.teacher.user')
            ->get();
    }

    /**
     * Open music batches the learner hasn't joined.
     *
     * @return Collection<int, Batch>
     */
    #[Computed]
    public function openBatches(): Collection
    {
        return Batch::query()
            ->open()
            ->forOffering(Offering::Music)
            ->whereNotIn('id', $this->learner->enrolments()->select('batch_id'))
            ->with('teacher.user')
            ->withCount(['enrolments as seats_taken' => fn ($query) => $query->whereIn('status', EnrolmentStatus::holdingSeat())])
            ->orderBy('subject')
            ->get();
    }

    /**
     * @return Collection<int, ClassSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        if ($this->learner->awaitingGuardianConsent()) {
            return collect();
        }

        return ClassSession::query()
            ->whereIn('batch_id', $this->enrolments->filter->hasAccess()->pluck('batch_id'))
            ->upcoming()
            ->where('starts_at', '<=', now()->addDays(14))
            ->with('batch.teacher.user')
            ->get();
    }

    public function requestSeat(int $batchId, RequestEnrolment $request): void
    {
        $this->authorize('request', [Enrolment::class, $this->learner]);

        $batch = Batch::query()->open()->forOffering(Offering::Music)->findOrFail($batchId);
        $request->handle($this->learner, $batch);

        unset($this->enrolments, $this->openBatches);
        Flux::toast(variant: 'success', text: __('Request sent. We will send you the fee payment link on WhatsApp; your place is confirmed when the fee is received.'));
    }

    public function cancelRequest(int $enrolmentId): void
    {
        $enrolment = Enrolment::query()->with('student')->findOrFail($enrolmentId);
        $this->authorize('cancel', $enrolment);

        $enrolment->delete();

        unset($this->enrolments, $this->openBatches);
        Flux::toast(text: __('Request cancelled.'));
    }

    /**
     * MUS-3: send the parent's approval email again, at most once every 10 minutes.
     */
    public function resendConsent(SendGuardianConsentRequest $send): void
    {
        abort_unless($this->learner->awaitingGuardianConsent(), 403);

        $key = 'guardian-consent:'.$this->learner->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            Flux::toast(variant: 'warning', text: __('We sent the email a few minutes ago. Please check your parent\'s inbox and spam folder.'));

            return;
        }
        RateLimiter::hit($key, 600);

        $send->handle($this->learner);
        Flux::toast(variant: 'success', text: __('Email sent again to :email.', ['email' => $this->learner->guardian_email]));
    }
}; ?>

<section class="flex w-full flex-col gap-8" wire:poll.60s>
    <div>
        <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:subheading>{{ __('Your music classes. The Join button opens 10 minutes before each class.') }}</flux:subheading>
    </div>

    @if ($this->learner->awaitingGuardianConsent())
        <flux:callout icon="envelope" color="amber" data-test="awaiting-guardian">
            <flux:callout.heading>{{ __('Waiting for your parent\'s approval') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('You are under 18, so we emailed your parent or guardian at :email. Once they click "I agree" in that email, you can join a batch here.', ['email' => $this->learner->guardian_email]) }}
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" wire:click="resendConsent">{{ __('Send the email again') }}</flux:button>
            </x-slot>
        </flux:callout>
    @else
        <x-portal.notices />

        @if ($this->sessions->isNotEmpty())
            <div>
                <flux:heading size="lg" class="mb-3">{{ __('Next two weeks') }}</flux:heading>
                <x-portal.timetable :sessions="$this->sessions" />
            </div>
        @endif

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('My batches') }}</flux:heading>

            @forelse ($this->enrolments as $enrolment)
                <flux:card class="flex flex-wrap items-start justify-between gap-4" wire:key="enrolment-{{ $enrolment->id }}">
                    <div class="space-y-1">
                        <flux:heading>{{ $enrolment->batch->name() }}</flux:heading>
                        <flux:text>{{ $enrolment->batch->scheduleSummary() }}@if ($enrolment->batch->teacher) · {{ $enrolment->batch->teacher->user->name }}@endif</flux:text>
                        <flux:text>{{ __(':fee a month', ['fee' => \App\Support\Money::format($enrolment->batch->monthly_fee)]) }}</flux:text>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <flux:badge :color="$enrolment->status->badgeColor()" size="sm">{{ $enrolment->status->label() }}</flux:badge>
                            @if ($enrolment->paid_through)
                                <flux:text size="sm">{{ __('Paid until :date', ['date' => $enrolment->paid_through->format('j M Y')]) }}</flux:text>
                            @endif
                        </div>
                        @if ($enrolment->inGracePeriod())
                            <flux:text size="sm" class="text-amber-700">{{ __('The fee is due. Classes continue for :days days after the paid-until date.', ['days' => \App\Models\Enrolment::GRACE_DAYS]) }}</flux:text>
                        @elseif ($enrolment->status === \App\Enums\EnrolmentStatus::Pending)
                            <flux:text size="sm">{{ __('We will send you the fee payment link on WhatsApp. Online payment on this page is coming soon.') }}</flux:text>
                        @elseif ($enrolment->status === \App\Enums\EnrolmentStatus::Expired)
                            <flux:text size="sm">{{ __('Classes are paused until the fee is renewed. Message us for the payment link.') }}</flux:text>
                        @endif
                    </div>
                    @can('cancel', $enrolment)
                        <flux:button size="sm" variant="ghost" wire:click="cancelRequest({{ $enrolment->id }})" wire:confirm="{{ __('Cancel this request?') }}">{{ __('Cancel request') }}</flux:button>
                    @endcan
                </flux:card>
            @empty
                <flux:text>{{ __('You have not joined a batch yet. Choose one below.') }}</flux:text>
            @endforelse
        </div>

        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Open batches') }}</flux:heading>
            @if ($this->openBatches->isNotEmpty())
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($this->openBatches as $batch)
                        <div class="flex flex-col gap-2 rounded-lg border border-zinc-200 bg-white p-4" wire:key="open-{{ $batch->id }}">
                            <p class="font-medium text-zinc-900">{{ $batch->name() }}</p>
                            <p class="text-sm text-zinc-600">{{ $batch->scheduleSummary() }}</p>
                            <p class="text-sm text-zinc-600">{{ __(':fee a month', ['fee' => \App\Support\Money::format($batch->monthly_fee)]) }}@if ($batch->teacher) · {{ $batch->teacher->user->name }}@endif</p>
                            @if ($batch->seats_taken >= $batch->max_seats)
                                <flux:badge color="zinc" size="sm" class="self-start">{{ __('Full') }}</flux:badge>
                            @else
                                <flux:button size="sm" variant="primary" class="self-start" wire:click="requestSeat({{ $batch->id }})">{{ __('Ask for a place') }}</flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text>{{ __('No open batches right now. Message us on WhatsApp and we will tell you when one starts.') }}</flux:text>
            @endif
            <flux:error name="batch" />
        </div>
    @endif
</section>
