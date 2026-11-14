@props(['samples', 'accent' => 'tuition'])

{{-- MKT-1: sample class clips. Shorts (and clips marked upright) are tall
     cards, two across on a phone; wide videos take two columns. Like the
     explainer videos, nothing loads from YouTube until the visitor taps
     play (resources/js/site.js), and only the video id is used. Each card
     carries its class and subject, ready for a filter by class later. --}}
@php($classes = \App\Filament\Resources\SiteSections\SiteSectionResource::classOptions())
<div class="grid grid-cols-2 items-start gap-4 sm:gap-6 md:grid-cols-4">
    @foreach ($samples as $sample)
        @php($id = \App\Support\YouTube::id($sample['url'] ?? ''))
        @if ($id !== null)
            @php($tall = \App\Support\YouTube::isShort($sample['url']) || ! empty($sample['tall']))
            @php($class = $classes[(string) ($sample['class'] ?? 'all')] ?? $classes['all'])
            <figure @class([
                'overflow-hidden rounded-xl border border-slate-200 bg-white',
                'col-span-1' => $tall,
                'col-span-2' => ! $tall,
            ]) data-class="{{ $sample['class'] ?? 'all' }}">
                <div @class([
                    'relative',
                    'aspect-[9/16]' => $tall,
                    'aspect-video' => ! $tall,
                    'bg-tuition-800' => $accent === 'tuition',
                    'bg-academic-800' => $accent === 'academic',
                    'bg-music-800' => $accent === 'music',
                    'bg-brand-800' => $accent === 'brand',
                ])>
                    <a href="https://www.youtube.com/watch?v={{ $id }}" target="_blank" rel="noopener" data-youtube="{{ $id }}" data-title="{{ $sample['title'] }}"
                       class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-white"
                       aria-label="Play: {{ $sample['title'] }}">
                        <span @class([
                            'grid size-14 place-items-center rounded-full bg-white/95 shadow-lg sm:size-16',
                            'text-tuition-800' => $accent === 'tuition',
                            'text-academic-800' => $accent === 'academic',
                            'text-music-800' => $accent === 'music',
                            'text-brand-800' => $accent === 'brand',
                        ]) aria-hidden="true">
                            <svg class="ml-1 size-6 sm:size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                        <span class="text-sm font-medium text-white/90" aria-hidden="true">Tap to play</span>
                    </a>
                </div>
                <figcaption class="p-3 sm:p-4">
                    <p class="font-semibold leading-snug text-slate-900">{{ $sample['title'] }}</p>
                    <p @class([
                        'mt-1 text-xs font-medium uppercase tracking-wide',
                        'text-tuition-700' => $accent === 'tuition',
                        'text-academic-700' => $accent === 'academic',
                        'text-music-700' => $accent === 'music',
                        'text-brand-700' => $accent === 'brand',
                    ])>{{ $class }}@if (filled($sample['subject'] ?? null)) · {{ $sample['subject'] }}@endif</p>
                </figcaption>
            </figure>
        @endif
    @endforeach
</div>
