@props(['testimonials'])

{{-- CMS-1: a quote, and optionally a screenshot of the message they sent
     (names, numbers and payments covered before upload). --}}
<div class="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($testimonials as $testimonial)
        <figure class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            @if (filled($testimonial['image'] ?? null))
                <img src="{{ $testimonial['image'] }}" alt="Message from {{ $testimonial['name'] }}: {{ $testimonial['quote'] }}" loading="lazy" decoding="async"
                     class="max-h-[28rem] w-full border-b border-slate-200 bg-slate-50 object-contain">
            @endif
            <div class="p-5">
                <blockquote class="text-slate-700">&ldquo;{{ $testimonial['quote'] }}&rdquo;</blockquote>
                <figcaption class="mt-4 text-sm">
                    <span class="font-semibold text-slate-900">{{ $testimonial['name'] }}</span>
                    <span class="block text-slate-500">{{ $testimonial['detail'] }}</span>
                </figcaption>
            </div>
        </figure>
    @endforeach
</div>
