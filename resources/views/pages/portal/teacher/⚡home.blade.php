<?php

use App\Enums\EnrolmentStatus;
use App\Models\Batch;
use App\Models\ClassSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| CLS-2: a teacher's batches and timetable. Only batches assigned to them.
*/
new #[Title('Teacher home')] class extends Component {
    /**
     * @return Collection<int, Batch>
     */
    #[Computed]
    public function batches(): Collection
    {
        $teacher = Auth::user()->teacher;

        return $teacher === null ? collect() : Batch::query()
            ->whereBelongsTo($teacher)
            ->where('is_active', true)
            ->with(['enrolments' => fn ($query) => $query->withAccess()->with('student')])
            ->orderBy('class_level')
            ->get();
    }

    /**
     * @return Collection<int, ClassSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        return ClassSession::query()
            ->whereIn('batch_id', $this->batches->pluck('id'))
            ->upcoming()
            ->where('starts_at', '<=', now()->addDays(14))
            ->with('batch.teacher.user')
            ->get();
    }
}; ?>

<section class="flex w-full flex-col gap-6" wire:poll.60s>
    <div>
        <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:subheading>{{ __('Your batches and classes for the next two weeks.') }}</flux:subheading>
    </div>

    @if (config('features.batches'))
        <x-portal.timetable :sessions="$this->sessions" />

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($this->batches as $batch)
                <flux:card wire:key="batch-{{ $batch->id }}">
                    <flux:heading>{{ $batch->name() }}</flux:heading>
                    <flux:text class="mt-1">{{ $batch->scheduleSummary() }}</flux:text>
                    <flux:text class="mt-3 font-medium text-zinc-800">{{ trans_choice(':count student|:count students', $batch->enrolments->count()) }}</flux:text>
                    <flux:text>{{ $batch->enrolments->pluck('student.first_name')->sort()->implode(', ') ?: __('No students yet') }}</flux:text>
                </flux:card>
            @endforeach
        </div>
    @else
        <flux:text>{{ __('The batches and orders assigned to you will appear here.') }}</flux:text>
    @endif
</section>
