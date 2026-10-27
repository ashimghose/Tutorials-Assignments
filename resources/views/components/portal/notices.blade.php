{{-- NOT-2: published notices for this parent or academic student. --}}
@if (config('features.chat'))
    @php($notices = \App\Models\Notice::query()->for(auth()->user())->limit(5)->get())
    @if ($notices->isNotEmpty())
        <div class="space-y-3" data-test="notices">
            @foreach ($notices as $notice)
                <flux:callout icon="megaphone" color="sky" wire:key="notice-{{ $notice->id }}">
                    <flux:callout.heading>{{ $notice->title }}</flux:callout.heading>
                    <flux:callout.text class="whitespace-pre-line">{{ $notice->body }}</flux:callout.text>
                    <flux:callout.text class="text-xs">{{ $notice->published_at?->setTimezone(config('app.display_timezone'))->format('j M Y') }}</flux:callout.text>
                </flux:callout>
            @endforeach
        </div>
    @endif
@endif
