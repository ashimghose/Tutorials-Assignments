@props(['eyebrow' => null, 'title', 'lead' => null])

<div {{ $attributes->class('max-w-2xl') }}>
    @if ($eyebrow)
        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $eyebrow }}</p>
    @endif
    <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h2>
    @if ($lead)
        <p class="mt-3 text-slate-600">{{ $lead }}</p>
    @endif
</div>
