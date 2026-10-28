<?php

use App\Models\AcademicOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| ACA-9: the academic orders assigned to this expert, open ones first.
*/
new #[Title('Assigned orders')] class extends Component {
    /**
     * @return Collection<int, AcademicOrder>
     */
    #[Computed]
    public function orders(): Collection
    {
        $teacher = Auth::user()->teacher;

        return $teacher === null ? collect() : AcademicOrder::query()
            ->whereBelongsTo($teacher, 'expert')
            ->with('status')
            ->orderBy('completed_at')
            ->orderBy('deadline')
            ->get();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Assigned orders') }}</flux:heading>
        <flux:subheading>{{ __('Academic orders the admin has assigned to you, by deadline.') }}</flux:subheading>
    </div>

    @forelse ($this->orders as $order)
        <a href="{{ route('teacher.orders.show', $order) }}" wire:navigate wire:key="order-{{ $order->id }}" class="block rounded-xl border border-zinc-200 bg-white p-4 hover:border-zinc-300">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-medium text-zinc-900">{{ $order->service_title }}</p>
                    <p class="text-sm text-zinc-600">{{ $order->reference() }} · {{ $order->level->label() }} · {{ __('due :date', ['date' => $order->deadline->format('j M Y')]) }}</p>
                </div>
                <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->name }}</flux:badge>
            </div>
        </a>
    @empty
        <flux:text>{{ __('No orders assigned to you yet.') }}</flux:text>
    @endforelse
</section>
