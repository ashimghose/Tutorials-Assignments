@props(['testimonials'])

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($testimonials as $testimonial)
        <figure class="rounded-xl border border-slate-200 bg-white p-5">
            <blockquote class="text-slate-700">&ldquo;{{ $testimonial['quote'] }}&rdquo;</blockquote>
            <figcaption class="mt-4 text-sm">
                <span class="font-semibold text-slate-900">{{ $testimonial['name'] }}</span>
                <span class="block text-slate-500">{{ $testimonial['detail'] }}</span>
            </figcaption>
        </figure>
    @endforeach
</div>
