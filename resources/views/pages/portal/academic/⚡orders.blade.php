<?php

use App\Models\AcademicOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
| ACA-8: an academic student's own orders, newest first.
*/
new #[Title('My orders')] class extends Component {
    /**
     * @return Collection<int, AcademicOrder>
     */
    #[Computed]
    public function orders(): Collection
    {
        $student = Auth::user()->academicStudent;

        return $student === null ? collect() : AcademicOrder::query()->whereBelongsTo($student)->with('status')->latest('id')->get();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:subheading>{{ __('Your orders and their progress.') }}</flux:subheading>
        </div>
        @if (config('features.academic_orders'))
            <flux:button variant="primary" icon="plus" :href="route('academic.orders.create')" wire:navigate>{{ __('New order') }}</flux:button>
        @endif
    </div>

    <x-portal.notices />

    @if (config('features.academic_orders'))
        @forelse ($this->orders as $order)
            <a href="{{ route('academic.orders.show', $order) }}" wire:navigate wire:key="order-{{ $order->id }}" class="block rounded-xl border border-zinc-200 bg-white p-4 hover:border-zinc-300">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-zinc-900">{{ $order->service_title }}</p>
                        <p class="text-sm text-zinc-600">{{ $order->reference() }} · {{ $order->level->label() }} · {{ __('due :date', ['date' => $order->deadline->format('j M Y')]) }}</p>
                    </div>
                    <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->name }}</flux:badge>
                </div>
            </a>
        @empty
            <flux:card class="text-center">
                <flux:heading>{{ __('No orders yet') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Order guidance, editing, formatting, a similarity check or printing for your research work.') }}</flux:text>
                <flux:button class="mt-4" variant="primary" :href="route('academic.orders.create')" wire:navigate>{{ __('Place an order') }}</flux:button>
            </flux:card>
        @endforelse
    @else
        <flux:card>
            <flux:text>{{ __('See our services and prices for research papers, synopses, theses and dissertations.') }}</flux:text>
            <flux:button class="mt-3" :href="route('academic')" variant="primary">{{ __('View academic services') }}</flux:button>
        </flux:card>
    @endif
</section>
