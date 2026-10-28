<x-site.layout :title="$campaign->title" :description="$campaign->offer ?: $campaign->headline" :section="$campaign->offering">
    {{-- MKT-2: a campaign landing page; its leads record the campaign. --}}
    <section @class(['bg-gradient-to-b to-white', 'from-tuition-50' => $campaign->offering === 'tuition', 'from-academic-50' => $campaign->offering === 'academic'])>
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-2">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $campaign->headline }}</h1>
                @if (filled($campaign->offer))
                    <p @class(['mt-4 inline-block rounded-lg px-4 py-2 font-semibold', 'bg-tuition-100 text-tuition-800' => $campaign->offering === 'tuition', 'bg-academic-100 text-academic-800' => $campaign->offering === 'academic'])>{{ $campaign->offer }}</p>
                @endif
                @if (filled($campaign->body))
                    <div class="article-body mt-6 text-slate-700">{!! \Illuminate\Support\Str::markdown($campaign->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                @endif
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-site.lead-form :action="route('campaign.store', $campaign)" :offering="$campaign->offering" :price-ranges="$priceRanges" :interest="$campaign->interest" :section="$campaign->offering" :page="'campaign:'.$campaign->slug" />
            </div>
        </div>
    </section>
</x-site.layout>
