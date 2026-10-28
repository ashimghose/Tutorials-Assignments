<?php

use App\Actions\Enrolments\RequestEnrolment;
use App\Enums\EnrolmentStatus;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\Student;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| CLS-1, CLS-2, PAY-4: a parent's view of their children's batches. They
| ask for a seat, which waits for the fee; the admin confirms the fee until
| online checkout is switched on.
*/
new #[Title('Classes')] class extends Component {
    /**
     * @return Collection<int, Student>
     */
    #[Computed]
    public function children(): Collection
    {
        return Student::query()
            ->whereBelongsTo(Auth::user()->parentProfile, 'parent')
            ->with(['enrolments' => fn ($query) => $query->where('status', '!=', EnrolmentStatus::Removed)->with('batch.teacher.user')])
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Open batches for each child's class.
     *
     * @return Collection<int, Collection<int, Batch>>
     */
    #[Computed]
    public function batchesByClass(): Collection
    {
        return Batch::query()
            ->open()
            ->whereIn('class_level', $this->children->pluck('class_level')->unique())
            ->with('teacher.user')
            ->withCount(['enrolments as seats_taken' => fn ($query) => $query->whereIn('status', EnrolmentStatus::holdingSeat())])
            ->orderBy('subject')
            ->get()
            ->groupBy('class_level');
    }

    /**
     * @return Collection<int, ClassSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        $batchIds = $this->children->flatMap->enrolments->filter->hasAccess()->pluck('batch_id')->unique();

        return ClassSession::query()
            ->whereIn('batch_id', $batchIds)
            ->upcoming()
            ->where('starts_at', '<=', now()->addDays(7))
            ->with('batch.teacher.user')
            ->get();
    }

    public function requestSeat(int $studentId, int $batchId, RequestEnrolment $request): void
    {
        $student = Student::query()->findOrFail($studentId);
        $this->authorize('request', [Enrolment::class, $student]);

        $batch = Batch::query()->open()->findOrFail($batchId);
        abort_unless($batch->class_level === $student->class_level, 403);

        $request->handle($student, $batch);

        unset($this->children, $this->batchesByClass);
        Flux::toast(variant: 'success', text: __('Request sent. We will send you the fee payment link on WhatsApp; the place is confirmed when the fee is received.'));
    }

    public function cancelRequest(int $enrolmentId): void
    {
        $enrolment = Enrolment::query()->with('student')->findOrFail($enrolmentId);
        $this->authorize('cancel', $enrolment);

        $enrolment->delete();

        unset($this->children, $this->batchesByClass);
        Flux::toast(text: __('Request cancelled.'));
    }
}; ?>

<section class="flex w-full flex-col gap-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Classes') }}</flux:heading>
        <flux:subheading>{{ __('Choose batches for your children and see their timetable.') }}</flux:subheading>
    </div>

    @if ($this->children->isEmpty())
        <flux:card class="text-center">
            <flux:heading>{{ __('Add a child first') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Add your child on the My children page, then come back to choose their classes.') }}</flux:text>
            <flux:button class="mt-4" variant="primary" :href="route('parent.children')" wire:navigate>{{ __('Go to My children') }}</flux:button>
        </flux:card>
    @endif

    @if ($this->sessions->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-3">{{ __('This week') }}</flux:heading>
            <x-portal.timetable :sessions="$this->sessions" />
        </div>
    @endif

    @foreach ($this->children as $child)
        <div class="space-y-4" wire:key="child-{{ $child->id }}">
            <flux:heading size="lg">{{ $child->first_name }} · {{ __('Class :class', ['class' => $child->class_level]) }}</flux:heading>

            @foreach ($child->enrolments as $enrolment)
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
            @endforeach

            @php($enrolledBatchIds = $child->enrolments->pluck('batch_id'))
            @php($open = ($this->batchesByClass[$child->class_level] ?? collect())->whereNotIn('id', $enrolledBatchIds))
            @if ($open->isNotEmpty())
                <div>
                    <flux:text class="mb-2 font-medium text-zinc-800">{{ __('Batches for Class :class', ['class' => $child->class_level]) }}</flux:text>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($open as $batch)
                            <div class="flex flex-col gap-2 rounded-lg border border-zinc-200 bg-white p-4" wire:key="open-{{ $child->id }}-{{ $batch->id }}">
                                <p class="font-medium text-zinc-900">{{ $batch->subject }} <span class="font-normal text-zinc-600">({{ $batch->board }})</span></p>
                                <p class="text-sm text-zinc-600">{{ $batch->scheduleSummary() }}</p>
                                <p class="text-sm text-zinc-600">{{ __(':fee a month', ['fee' => \App\Support\Money::format($batch->monthly_fee)]) }}@if ($batch->teacher) · {{ $batch->teacher->user->name }}@endif</p>
                                @if ($batch->seats_taken >= $batch->max_seats)
                                    <flux:badge color="zinc" size="sm" class="self-start">{{ __('Full') }}</flux:badge>
                                @else
                                    <flux:button size="sm" variant="primary" class="self-start" wire:click="requestSeat({{ $child->id }}, {{ $batch->id }})">{{ __('Ask for a place') }}</flux:button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($child->enrolments->isEmpty())
                <flux:text>{{ __('No open batches for Class :class right now. Message us and we will tell you when one starts.', ['class' => $child->class_level]) }}</flux:text>
            @endif
            <flux:error name="batch" />
        </div>
    @endforeach
</section>
