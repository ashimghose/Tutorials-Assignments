<?php

use App\Models\ClassSession;
use App\Models\Enrolment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| CLS-2: a school student's timetable. Only batches where the enrolment
| gives access today (CLS-3).
*/
new #[Title('My classes')] class extends Component {
    /**
     * @return Collection<int, Enrolment>
     */
    #[Computed]
    public function enrolments(): Collection
    {
        $student = Auth::user()->student;

        return $student === null ? collect() : Enrolment::query()->whereBelongsTo($student)->with('batch')->get();
    }

    /**
     * @return Collection<int, ClassSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        $batchIds = $this->enrolments->filter->hasAccess()->pluck('batch_id');

        return ClassSession::query()
            ->whereIn('batch_id', $batchIds)
            ->upcoming()
            ->where('starts_at', '<=', now()->addDays(14))
            ->with('batch.teacher.user')
            ->get();
    }
}; ?>

<section class="flex w-full flex-col gap-6" wire:poll.60s>
    <div>
        <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:subheading>{{ __('Your classes for the next two weeks. The Join button opens 10 minutes before each class.') }}</flux:subheading>
    </div>

    @if (config('features.batches'))
        @foreach ($this->enrolments->reject->hasAccess() as $enrolment)
            <flux:callout icon="pause-circle" color="amber" wire:key="paused-{{ $enrolment->id }}">
                <flux:callout.heading>{{ $enrolment->batch->name() }}: {{ $enrolment->status->label() }}</flux:callout.heading>
                <flux:callout.text>{{ __('Ask your parent to check the fee for this batch.') }}</flux:callout.text>
            </flux:callout>
        @endforeach

        <x-portal.timetable :sessions="$this->sessions" :empty="__('No classes yet. They appear here once your parent enrols you in a batch.')" />
    @else
        <flux:text>{{ __('Your classes and recordings will appear here once your parent enrols you in a batch.') }}</flux:text>
    @endif
</section>
