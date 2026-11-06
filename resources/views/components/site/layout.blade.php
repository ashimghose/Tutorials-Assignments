@props([
    'title' => null,
    'description',
    'section' => 'general',
    'type' => 'website',
    'robots' => null,
    'image' => '/images/social/share.png',
])

@php
    // VRT-5: a section's pages can carry its own brand name and logo.
    $brand = \App\Support\Verticals::brand($section);
    $appName = $brand['name'];
    $fullTitle = filled($title) ? $title.' | '.$appName : $appName;
    // VRT-3: shared pages reached on a section's own domain point Google at the main domain.
    $hostSection = \App\Support\Verticals::forHost(request()->getHost());
    $canonical = $hostSection !== null && request()->path() !== '/'
        ? \App\Support\Verticals::mainUrl(request()->path())
        : url()->current();
    $home = \App\Support\SiteContent::section('home');
    $banner = $home['banner'] ?? [];
    $sectionLinks = collect(\App\Enums\Offering::sections())
        ->map(fn (\App\Enums\Offering $offering) => ['label' => $offering->label(), 'route' => $offering->value, 'href' => \App\Support\Verticals::url($offering->value)])
        ->all();
    $nav = [
        ...$sectionLinks,
        ['label' => 'Resources', 'route' => 'resources.index', 'href' => route('resources.index')],
        ['label' => 'About', 'route' => 'about', 'href' => route('about')],
        ['label' => 'Contact', 'route' => 'contact', 'href' => route('contact')],
    ];
    // HIR-1: the Careers pages, while the site's own forms are on.
    if (config('features.cms')) {
        array_splice($nav, -1, 0, [['label' => 'Careers', 'route' => 'careers.index', 'href' => route('careers.index')]]);
    }
    $footerSections = [
        'tuition' => [['Fees', 'fees'], ['How it works', 'how-it-works'], ['Questions', 'faqs']],
        'academic' => [['Price list', 'prices'], ['How it works', 'how-it-works'], ['Questions', 'faqs']],
        'music' => [['Fees', 'fees'], ['How it works', 'how-it-works'], ['Questions', 'faqs']],
    ];
    // Phase 2+: a way in for parents, students, experts and the admin.
    $account = config('features.accounts')
        ? (auth()->check() ? ['label' => 'My account', 'route' => 'dashboard'] : ['label' => 'Log in', 'route' => 'login'])
        : null;
    // Google Analytics: public pages, visitors who aren't logged in, a valid G- id only.
    $gaId = config('site.google_analytics_id');
    $gaId = filled($gaId) && preg_match('/^G-[A-Z0-9]{4,20}$/', $gaId) && ! auth()->check() ? $gaId : null;
@endphp

<!DOCTYPE html>
<html lang="en-IN" class="scroll-pt-24">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $canonical }}">
        @if ($robots)
            <meta name="robots" content="{{ $robots }}">
        @endif

        <meta property="og:type" content="{{ $type }}">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:title" content="{{ $fullTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:locale" content="en_IN">
        {{-- The preview card on WhatsApp, Facebook and other shares. --}}
        <meta property="og:image" content="{{ url($image) }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="theme-color" content="#0b5d45">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/site.css', 'resources/js/site.js'])

        {{ $head ?? '' }}

        @if (filled(config('site.analytics_token')))
            {{-- Cloudflare Web Analytics: cookie-less, public pages only. --}}
            <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{{ json_encode(['token' => config('site.analytics_token')]) }}'></script>
        @endif
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2">Skip to content</a>

        @if (filled($banner['text'] ?? null))
            {{-- CMS-1: a site-wide notice the admin can switch on. --}}
            <div class="bg-brand-900 px-4 py-2 text-center text-sm text-white">
                @if (filled($banner['link'] ?? null))
                    <a href="{{ $banner['link'] }}" class="underline underline-offset-2 hover:no-underline">{{ $banner['text'] }}</a>
                @else
                    {{ $banner['text'] }}
                @endif
            </div>
        @endif

        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-20 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex flex-col items-start">
                    {{-- The owner's logo artwork; its text is the alt text. Icon-only version: indian-assignments-icon.svg. --}}
                    <img src="{{ $brand['logo'] }}" alt="{{ $brand['logo_alt'] }}" width="123" height="48" class="h-10 w-auto sm:h-11">
                    @if (filled(config('verticals.brand.tagline')))
                        <span class="mt-0.5 text-[11px] leading-tight font-semibold tracking-wide text-tuition-700">{{ config('verticals.brand.tagline') }}</span>
                        <span class="text-[10px] leading-tight tracking-wide text-brand-800">{{ config('verticals.brand.tagline_en') }}</span>
                    @endif
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                    @foreach ($nav as $item)
                        <a href="{{ $item['href'] }}"
                           @class([
                               'rounded-md px-3 py-2 text-sm font-medium',
                               'bg-brand-50 text-brand-800' => request()->routeIs($item['route'].'*'),
                               'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['route'].'*'),
                           ])
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                    @if ($account)
                        <a href="{{ route($account['route']) }}" class="ml-2 rounded-md border border-brand-700 px-3 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-50">{{ $account['label'] }}</a>
                    @endif
                </nav>

                {{-- Mobile menu: <details> needs no JavaScript. --}}
                <details class="group relative lg:hidden">
                    <summary class="flex size-10 items-center justify-center rounded-md text-slate-700 hover:bg-slate-100" aria-label="Menu">
                        <svg class="size-6 group-open:hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                        <svg class="hidden size-6 group-open:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                    </summary>
                    <nav class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg" aria-label="Main">
                        @foreach ($nav as $item)
                            <a href="{{ $item['href'] }}" class="block rounded-md px-3 py-3 font-medium text-slate-700 hover:bg-slate-100">{{ $item['label'] }}</a>
                        @endforeach
                        @if ($account)
                            <a href="{{ route($account['route']) }}" class="mt-1 block rounded-md border-t border-slate-200 px-3 py-3 font-semibold text-brand-800 hover:bg-brand-50">{{ $account['label'] }}</a>
                        @endif
                    </nav>
                </details>
            </div>
        </header>

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-slate-50">
            <div @class(['mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6', 'lg:grid-cols-4' => count($sectionLinks) < 3, 'lg:grid-cols-5' => count($sectionLinks) >= 3])>
                <div>
                    <p class="font-bold text-brand-900">{{ $appName }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $home['text']['footer'] }}</p>
                </div>
                @foreach ($sectionLinks as $item)
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($footerSections[$item['route']] as [$label, $fragment])
                                <li><a href="{{ \App\Support\Verticals::url($item['route'], $fragment) }}" class="text-slate-600 hover:text-slate-900">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <div>
                    <p class="text-sm font-semibold text-slate-900">Contact</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li><a href="{{ \App\Support\SiteLinks::whatsapp($section) }}" target="_blank" rel="noopener" class="hover:text-slate-900">WhatsApp us</a></li>
                        @if (filled(config('site.contact.email')))
                            <li><a href="mailto:{{ config('site.contact.email') }}" class="hover:text-slate-900">{{ config('site.contact.email') }}</a></li>
                        @endif
                        <li>{{ config('site.contact.office_hours') }}</li>
                        @if (config('features.cms'))
                            {{-- HIR-1: the Careers pages. --}}
                            <li><a href="{{ route('careers.index') }}" class="font-medium text-brand-800 hover:text-slate-900">Teach with us: we're hiring</a></li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-200">
                <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 pt-6 pb-24 text-sm text-slate-500 sm:pb-6 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p>&copy; {{ now()->year }} {{ $appName }}</p>
                    <nav class="flex flex-wrap gap-x-5 gap-y-2" aria-label="Policies">
                        <a href="{{ route('privacy') }}" class="hover:text-slate-900">Privacy policy</a>
                        <a href="{{ route('terms') }}" class="hover:text-slate-900">Terms</a>
                        <a href="{{ route('refunds') }}" class="hover:text-slate-900">Refund policy</a>
                        @if ($gaId)
                            <button type="button" data-cookie-settings class="hover:text-slate-900">Cookie settings</button>
                        @endif
                    </nav>
                </div>
            </div>
        </footer>

        <x-site.whatsapp-button :section="$section" />

        @if ($gaId)
            <x-site.cookie-consent :measurement-id="$gaId" />
        @endif
    </body>
</html>
