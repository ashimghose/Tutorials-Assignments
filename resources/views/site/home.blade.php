<x-site.layout :title="$content['text']['seo_title']" :description="$content['text']['seo_description']">
    <x-slot:head>
        {{-- Tells Google who runs the site, for the knowledge panel and sitelinks. --}}
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'EducationalOrganization',
                    '@id' => route('home').'#organization',
                    'name' => config('app.name'),
                    'url' => route('home'),
                    'logo' => url('/apple-touch-icon.png'),
                    'image' => url('/images/social/share.png'),
                    'description' => $content['text']['seo_description'],
                    'email' => config('site.contact.email'),
                    'areaServed' => ['@type' => 'Country', 'name' => 'India'],
                    'address' => filled(config('site.contact.city'))
                        ? ['@type' => 'PostalAddress', 'addressLocality' => config('site.contact.city'), 'addressCountry' => 'IN']
                        : null,
                ]),
                [
                    '@type' => 'WebSite',
                    'name' => config('app.name'),
                    'url' => route('home'),
                    'inLanguage' => 'en-IN',
                    'publisher' => ['@id' => route('home').'#organization'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    </x-slot:head>

    <section class="bg-gradient-to-b from-brand-50 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $content['hero']['title'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $content['hero']['lead'] }}</p>
            </div>
            <x-site.hero-image name="home-hero" alt="A live online class on a laptop beside an open book, a graduation cap and bound theses" />
        </div>
    </section>

    @php
        // VRT-1: one card per switched-on section. Full class names so Tailwind keeps them.
        $cardStyles = [
            'tuition' => ['card' => 'border-tuition-100 bg-tuition-50', 'text' => 'text-tuition-700', 'button' => 'bg-tuition-700 hover:bg-tuition-800'],
            'academic' => ['card' => 'border-academic-100 bg-academic-50', 'text' => 'text-academic-700', 'button' => 'bg-academic-700 hover:bg-academic-800'],
            'music' => ['card' => 'border-music-100 bg-music-50', 'text' => 'text-music-700', 'button' => 'bg-music-700 hover:bg-music-800'],
        ];
        $cards = collect(\App\Support\Verticals::enabledKeys())->filter(fn (string $key) => isset($content['offerings'][$key]))
            ->mapWithKeys(fn (string $key) => [$key => $content['offerings'][$key]])->all();
    @endphp

    <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <div @class(['grid gap-6 md:grid-cols-2', 'lg:grid-cols-3' => count($cards) >= 3])>
            @foreach ($cards as $key => $card)
                <article class="flex flex-col rounded-2xl border p-6 sm:p-8 {{ $cardStyles[$key]['card'] }}">
                    <p class="text-sm font-semibold uppercase tracking-wide {{ $cardStyles[$key]['text'] }}">{{ $card['eyebrow'] }}</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $card['title'] }}</h2>
                    <p class="mt-3 text-slate-700">{{ $card['body'] }}</p>
                    <ul class="mt-5 space-y-2 text-slate-700">
                        @foreach ($card['points'] as $point)
                            <li class="flex gap-2"><span aria-hidden="true" class="{{ $cardStyles[$key]['text'] }}">&#10003;</span> {{ $point }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-auto pt-6">
                        <a href="{{ \App\Support\Verticals::url($key) }}" class="inline-flex min-h-12 items-center rounded-lg px-5 py-3 font-semibold text-white {{ $cardStyles[$key]['button'] }}">{{ $content['text'][$key.'_button'] }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <x-site.section-heading :title="$content['text']['guides_title']" :lead="$content['text']['guides_lead']" />
            <div class="mt-8">
                <x-site.article-list :articles="$articles" />
            </div>
            <a href="{{ route('resources.index') }}" class="mt-6 inline-block font-semibold text-brand-700 hover:underline">{{ $content['text']['guides_link'] }}</a>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="rounded-2xl bg-brand-900 px-6 py-10 text-white sm:px-10">
            <h2 class="text-2xl font-bold">{{ $content['text']['help_title'] }}</h2>
            <p class="mt-2 max-w-2xl text-brand-100">{{ $content['text']['help_body'] }}</p>
            <p class="mt-1 text-sm text-brand-100">{{ config('site.contact.office_hours') }}</p>
            <a href="{{ \App\Support\SiteLinks::whatsapp('general') }}" target="_blank" rel="noopener" class="mt-6 inline-flex min-h-12 items-center gap-2 rounded-lg bg-white px-5 py-3 font-semibold text-slate-900 hover:bg-slate-100">
                <x-site.whatsapp-icon class="size-5 text-[#1a8f4a]" /> {{ $content['text']['help_button'] }}
            </a>
        </div>
    </section>
</x-site.layout>
