@props(['order'])

{{-- ACA-7: every status change, oldest first, in India time. --}}
<ol {{ $attributes->class('space-y-3 border-s border-zinc-200 ps-4') }}>
    @foreach ($order->history as $step)
        <li class="relative" wire:key="history-{{ $step->id }}">
            <span class="absolute -start-[1.4rem] top-1.5 size-2.5 rounded-full bg-brand-600" aria-hidden="true"></span>
            <p class="font-medium text-zinc-900">{{ $step->toStatus->name }}</p>
            <p class="text-sm text-zinc-500">{{ $step->created_at->setTimezone(config('app.display_timezone'))->format('j M Y, g:i a') }}</p>
            @if ($step->note)
                <p class="text-sm text-zinc-700">{{ $step->note }}</p>
            @endif
        </li>
    @endforeach
</ol>
@if ($order->tracking_number)
    <p class="mt-3 text-sm text-zinc-700">{{ __('Courier: :courier, tracking number :number', ['courier' => $order->courier_name, 'number' => $order->tracking_number]) }}</p>
@endif
