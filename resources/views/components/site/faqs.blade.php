@props(['faqs'])

<div class="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
    @foreach ($faqs as $faq)
        <details class="group p-5">
            <summary class="flex items-start justify-between gap-4 font-semibold text-slate-900">
                <span>{{ $faq['q'] }}</span>
                <svg class="mt-1 size-5 shrink-0 text-slate-500 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </summary>
            <p class="mt-3 text-slate-600">{{ $faq['a'] }}</p>
        </details>
    @endforeach
</div>

{{-- CMS-4: FAQ structured data for search results. --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn ($faq) => [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
    ])->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
