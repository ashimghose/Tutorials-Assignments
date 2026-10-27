@props(['order'])

{{-- Private files: each link is signed and lasts 10 minutes; the download checks access again. --}}
<ul {{ $attributes->class('divide-y divide-zinc-200') }}>
    @forelse ($order->files->sortByDesc('id') as $file)
        <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="file-{{ $file->id }}">
            <div class="min-w-0">
                <p class="truncate font-medium text-zinc-900">{{ $file->original_name }}</p>
                <p class="text-sm text-zinc-500">{{ $file->kind->label() }} · {{ \Illuminate\Support\Number::fileSize($file->size) }} · {{ $file->created_at->setTimezone(config('app.display_timezone'))->format('j M Y') }}</p>
            </div>
            <flux:button size="sm" icon="arrow-down-tray" :href="\Illuminate\Support\Facades\URL::temporarySignedRoute('orders.files.download', now()->addMinutes(10), ['orderFile' => $file])">{{ __('Download') }}</flux:button>
        </li>
    @empty
        <li class="py-2 text-sm text-zinc-500">{{ __('No files yet.') }}</li>
    @endforelse
</ul>
