@props(['sessions', 'empty' => __('No classes in the next two weeks.')])

{{-- CLS-2: upcoming classes in India time. Join opens 10 minutes before the start; the server checks again on click. --}}
@php($tz = config('app.display_timezone'))
<div {{ $attributes->class('space-y-3') }}>
    @forelse ($sessions->groupBy(fn ($session) => $session->starts_at->setTimezone($tz)->toDateString()) as $date => $day)
        <div>
            <flux:heading size="sm" class="mb-2 text-zinc-500">{{ \Illuminate\Support\Carbon::parse($date)->isoFormat('dddd, D MMMM') }}</flux:heading>
            <div class="space-y-2">
                @foreach ($day as $session)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3" wire:key="session-{{ $session->id }}">
                        <div>
                            <p class="font-medium text-zinc-900 {{ $session->isCancelled() ? 'line-through' : '' }}">{{ $session->batch->name() }}</p>
                            <p class="text-sm text-zinc-600">
                                {{ $session->starts_at->setTimezone($tz)->format('g:i a') }} – {{ $session->ends_at->setTimezone($tz)->format('g:i a') }}
                                @if ($session->batch->teacher)
                                    · {{ $session->batch->teacher->user->name }}
                                @endif
                            </p>
                        </div>
                        @if ($session->isCancelled())
                            <flux:badge color="zinc" size="sm">{{ __('Cancelled') }}</flux:badge>
                        @elseif ($session->isJoinOpen())
                            <flux:button size="sm" variant="primary" icon="video-camera" :href="route('classes.join', $session)" target="_blank">{{ __('Join') }}</flux:button>
                        @else
                            <flux:button size="sm" icon="video-camera" disabled>{{ __('Join') }}</flux:button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <flux:text>{{ $empty }}</flux:text>
    @endforelse
</div>
