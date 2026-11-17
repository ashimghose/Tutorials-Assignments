@props(['samples', 'moreLabel', 'openLabel'])

{{-- MKT-1: guidance samples on /academic. The first three show; the rest
     open under a "See all samples" button (no extra page, works without
     JavaScript). --}}
@php($samples = array_values(array_filter(is_array($samples) ? $samples : [], 'is_array')))
@php($first = array_slice($samples, 0, 3))
@php($rest = array_slice($samples, 3))
<div class="flex flex-wrap items-start gap-4 [--card-h:17rem] sm:gap-6 sm:[--card-h:18rem]">
    @foreach ($first as $sample)
        <x-site.academic-sample :sample="$sample" :open-label="$openLabel" />
    @endforeach
</div>
@if ($rest !== [])
    <details class="group mt-6" data-more-samples>
        <summary class="inline-flex min-h-12 cursor-pointer list-none items-center gap-2 rounded-lg border border-academic-700 bg-white px-5 py-3 font-semibold text-academic-800 hover:bg-academic-50 [&::-webkit-details-marker]:hidden">
            {{ $moreLabel }} ({{ count($samples) }})
            <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06z" clip-rule="evenodd"/></svg>
        </summary>
        <div class="mt-6 flex flex-wrap items-start gap-4 [--card-h:17rem] sm:gap-6 sm:[--card-h:18rem]">
            @foreach ($rest as $sample)
                <x-site.academic-sample :sample="$sample" :open-label="$openLabel" />
            @endforeach
        </div>
    </details>
@endif
