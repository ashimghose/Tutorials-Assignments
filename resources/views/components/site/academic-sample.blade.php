@props(['sample', 'openLabel'])

{{-- MKT-1: one guidance sample card. Videos are wide 16:9 or tall 9:16
     (Shorts and upright clips) and show YouTube's still picture until the
     visitor taps play (resources/js/site.js). A PDF is a page-shaped card
     that opens the file in a new tab. All cards share one height. --}}
@php($kind = ($sample['kind'] ?? 'video') === 'pdf' ? 'pdf' : 'video')
@php($tags = implode(' · ', array_filter([$sample['level'] ?? null, $sample['subject'] ?? null], 'filled')))
@if ($kind === 'pdf' && filled($sample['file'] ?? null))
    @php($href = \App\Support\SiteMedia::sampleUrl($sample['file']))
    <figure class="w-[calc(var(--card-h)*12/16)] max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" data-sample="pdf">
        <a href="{{ $href }}" target="_blank" rel="noopener" class="group relative block aspect-[4/3] overflow-hidden bg-academic-50" aria-label="{{ $openLabel }}: {{ $sample['title'] }}">
            <span class="absolute inset-x-6 top-4 bottom-0 rounded-t-md border border-b-0 border-academic-100 bg-white p-4 shadow-sm transition duration-300 group-hover:-translate-y-1" aria-hidden="true">
                <span class="block h-2 w-2/3 rounded bg-academic-100"></span>
                <span class="mt-3 block h-1.5 w-full rounded bg-slate-200"></span>
                <span class="mt-2 block h-1.5 w-5/6 rounded bg-slate-200"></span>
                <span class="mt-2 block h-1.5 w-full rounded bg-slate-200"></span>
                <span class="mt-2 block h-1.5 w-3/4 rounded bg-slate-200"></span>
            </span>
            <span class="absolute right-3 bottom-3 rounded-md bg-academic-800 px-2 py-1 text-xs font-bold tracking-wide text-white">PDF</span>
        </a>
        <figcaption class="p-3 sm:p-4">
            <p class="font-semibold leading-snug text-slate-900">{{ $sample['title'] }}</p>
            @if ($tags !== '')
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-academic-700">{{ $tags }}</p>
            @endif
            @if (filled($sample['note'] ?? null))
                <p class="mt-2 text-sm text-slate-600">{{ $sample['note'] }}</p>
            @endif
            <a href="{{ $href }}" target="_blank" rel="noopener" class="mt-3 inline-flex min-h-11 items-center gap-1 font-semibold text-academic-800 underline-offset-4 hover:underline">{{ $openLabel }} <span aria-hidden="true">&rarr;</span></a>
        </figcaption>
    </figure>
@elseif ($kind === 'video' && ($id = \App\Support\YouTube::id($sample['url'] ?? '')) !== null)
    @php($tall = \App\Support\YouTube::isShort($sample['url']) || ! empty($sample['tall']))
    <figure @class([
        'max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm',
        'w-[calc(var(--card-h)*9/16)]' => $tall,
        'w-[calc(var(--card-h)*16/9)]' => ! $tall,
    ]) data-sample="video">
        <div @class(['relative bg-academic-800', 'aspect-[9/16]' => $tall, 'aspect-video' => ! $tall])>
            <a href="https://www.youtube.com/watch?v={{ $id }}" target="_blank" rel="noopener" data-youtube="{{ $id }}" data-title="{{ $sample['title'] }}"
               class="group absolute inset-0 grid place-items-center overflow-hidden text-white" aria-label="Play: {{ $sample['title'] }}">
                <img src="https://i.ytimg.com/vi/{{ $id }}/{{ $tall ? 'hqdefault' : 'mqdefault' }}.jpg" alt="" loading="lazy" decoding="async" onerror="this.remove()"
                     class="absolute inset-0 size-full object-cover transition duration-300 group-hover:scale-105">
                <span class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-black/10" aria-hidden="true"></span>
                <span class="relative grid size-14 place-items-center rounded-full bg-white/95 text-academic-800 shadow-lg transition group-hover:scale-110 sm:size-16" aria-hidden="true">
                    <svg class="ml-1 size-6 sm:size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </a>
        </div>
        <figcaption class="p-3 sm:p-4">
            <p class="font-semibold leading-snug text-slate-900">{{ $sample['title'] }}</p>
            @if ($tags !== '')
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-academic-700">{{ $tags }}</p>
            @endif
            @if (filled($sample['note'] ?? null))
                <p class="mt-2 text-sm text-slate-600">{{ $sample['note'] }}</p>
            @endif
        </figcaption>
    </figure>
@endif
