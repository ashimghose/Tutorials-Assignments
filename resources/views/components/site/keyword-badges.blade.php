@props(['keywords' => [], 'accent' => 'brand'])

{{-- MKT-1: the section's main services as highlighted badges under the
     headline, so the headline itself can stay short. Edited in the admin
     (Website > Pages > section > Top of page > Highlighted keywords). --}}
@if (array_filter((array) $keywords) !== [])
    <ul class="mt-5 flex flex-wrap gap-2" aria-label="What we offer">
        @foreach (array_filter((array) $keywords) as $keyword)
            <li @class([
                'rounded-full px-3 py-1.5 text-sm font-semibold sm:text-base',
                'bg-tuition-100 text-tuition-800' => $accent === 'tuition',
                'bg-academic-100 text-academic-800' => $accent === 'academic',
                'bg-music-100 text-music-800' => $accent === 'music',
            ])>{{ $keyword }}</li>
        @endforeach
    </ul>
@endif
