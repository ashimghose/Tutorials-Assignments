@props([
    'form',
    'section',
    'page',
    'interest' => null,
    'variant' => 'primary',
])

{{-- MKT-3, MKT-4: link to a Google Form, pre-filled with the page, the
     interest and (in the browser) the visitor's campaign source. --}}
@php
    $link = \App\Support\SiteLinks::form($form, $section, $page, $interest);
    $classes = match ($variant) {
        'primary' => 'bg-brand-800 text-white hover:bg-brand-900',
        'tuition' => 'bg-tuition-700 text-white hover:bg-tuition-800',
        'academic' => 'bg-academic-700 text-white hover:bg-academic-800',
        'light' => 'bg-white text-slate-900 hover:bg-slate-100',
        default => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50',
    };
@endphp

<a href="{{ $link['href'] }}"
   target="_blank" rel="noopener"
   data-form="{{ $form }}"
   @if ($link['utm_entries'] !== []) data-utm-entries="{{ json_encode($link['utm_entries']) }}" @endif
   {{ $attributes->class(['inline-flex min-h-12 items-center justify-center gap-2 rounded-lg px-5 py-3 text-center font-semibold', $classes]) }}>
    {{ $slot }}
</a>
