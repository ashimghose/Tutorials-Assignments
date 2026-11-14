@props(['samples', 'accent' => 'tuition'])

{{-- MKT-1: sample class clips. Every card in a row has the same height, and
     its width follows the video's shape: Shorts (and clips marked upright)
     are narrow 9:16 cards, wide videos 16:9, so a row stays tidy whatever
     the mix. Each card shows the video's own still picture from YouTube's
     image server (a plain image, no cookies); the player itself loads only
     after the visitor taps play (resources/js/site.js). Each card carries
     its class and subject, ready for a filter by class later. --}}
@php($classes = \App\Filament\Resources\SiteSections\SiteSectionResource::classOptions())
<div class="flex flex-wrap gap-4 [--card-h:17rem] sm:gap-6 sm:[--card-h:18rem]">
    @foreach ($samples as $sample)
        @php($id = \App\Support\YouTube::id($sample['url'] ?? ''))
        @if ($id !== null)
            @php($tall = \App\Support\YouTube::isShort($sample['url']) || ! empty($sample['tall']))
            @php($class = $classes[(string) ($sample['class'] ?? 'all')] ?? $classes['all'])
            <figure @class([
                'max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm',
                'w-[calc(var(--card-h)*9/16)]' => $tall,
                'w-[calc(var(--card-h)*16/9)]' => ! $tall,
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
                       class="group absolute inset-0 grid place-items-center overflow-hidden text-white"
                       aria-label="Play: {{ $sample['title'] }}">
                        <img src="https://i.ytimg.com/vi/{{ $id }}/{{ $tall ? 'hqdefault' : 'mqdefault' }}.jpg" alt="" loading="lazy" decoding="async" onerror="this.remove()"
                             class="absolute inset-0 size-full object-cover transition duration-300 group-hover:scale-105">
                        <span class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-black/10" aria-hidden="true"></span>
                        <span @class([
                            'relative grid size-14 place-items-center rounded-full bg-white/95 shadow-lg transition group-hover:scale-110 sm:size-16',
                            'text-tuition-800' => $accent === 'tuition',
                            'text-academic-800' => $accent === 'academic',
                            'text-music-800' => $accent === 'music',
                            'text-brand-800' => $accent === 'brand',
                        ]) aria-hidden="true">
                            <svg class="ml-1 size-6 sm:size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </span>
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
