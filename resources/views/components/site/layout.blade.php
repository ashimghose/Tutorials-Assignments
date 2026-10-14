@props([
    'title' => null,
    'description',
    'section' => 'general',
    'type' => 'website',
    'robots' => null,
])

@php
    $appName = config('app.name');
    $fullTitle = filled($title) ? $title.' | '.$appName : $appName;
    $canonical = url()->current();
    $initials = collect(preg_split('/\s+/', $appName))
        ->reject(fn ($word) => in_array(strtolower($word), ['and', 'of', 'the', '&']))
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->implode('');
    $nav = [
        ['label' => 'Tuition', 'route' => 'tuition'],
        ['label' => 'Academic services', 'route' => 'academic'],
        ['label' => 'Resources', 'route' => 'resources.index'],
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

<!DOCTYPE html>
<html lang="en-IN" class="scroll-pt-20">
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
        <meta name="twitter:card" content="summary">
        <meta name="theme-color" content="#1f3c8f">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/site.css', 'resources/js/site.js'])

        @if (filled(config('site.analytics_token')))
            {{-- Cloudflare Web Analytics: cookie-less, public pages only. --}}
            <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{{ json_encode(['token' => config('site.analytics_token')]) }}'></script>
        @endif
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2">Skip to content</a>

        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-brand-900">
                    <span class="grid size-9 place-items-center rounded-lg bg-brand-800 text-sm text-white" aria-hidden="true">{{ $initials }}</span>
                    <span class="text-base leading-tight sm:text-lg">{{ $appName }}</span>
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                    @foreach ($nav as $item)
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'rounded-md px-3 py-2 text-sm font-medium',
                               'bg-brand-50 text-brand-800' => request()->routeIs($item['route'].'*'),
                               'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['route'].'*'),
                           ])
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                {{-- Mobile menu: <details> needs no JavaScript. --}}
                <details class="group relative lg:hidden">
                    <summary class="flex size-10 items-center justify-center rounded-md text-slate-700 hover:bg-slate-100" aria-label="Menu">
                        <svg class="size-6 group-open:hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                        <svg class="hidden size-6 group-open:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                    </summary>
                    <nav class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg" aria-label="Main">
                        @foreach ($nav as $item)
                            <a href="{{ route($item['route']) }}" class="block rounded-md px-3 py-3 font-medium text-slate-700 hover:bg-slate-100">{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                </details>
            </div>
        </header>

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-slate-50">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
                <div>
                    <p class="font-bold text-brand-900">{{ $appName }}</p>
                    <p class="mt-2 text-sm text-slate-600">Live online tuition for classes 1 to 12, and academic guidance, editing and printing for college students.</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Tuition</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('tuition') }}#fees" class="text-slate-600 hover:text-slate-900">Fees</a></li>
                        <li><a href="{{ route('tuition') }}#how-it-works" class="text-slate-600 hover:text-slate-900">How it works</a></li>
                        <li><a href="{{ route('tuition') }}#faqs" class="text-slate-600 hover:text-slate-900">Questions</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Academic services</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('academic') }}#prices" class="text-slate-600 hover:text-slate-900">Price list</a></li>
                        <li><a href="{{ route('academic') }}#how-it-works" class="text-slate-600 hover:text-slate-900">How it works</a></li>
                        <li><a href="{{ route('academic') }}#faqs" class="text-slate-600 hover:text-slate-900">Questions</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Contact</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li><a href="{{ \App\Support\SiteLinks::whatsapp($section) }}" target="_blank" rel="noopener" class="hover:text-slate-900">WhatsApp us</a></li>
                        @if (filled(config('site.contact.email')))
                            <li><a href="mailto:{{ config('site.contact.email') }}" class="hover:text-slate-900">{{ config('site.contact.email') }}</a></li>
                        @endif
                        <li>{{ config('site.contact.office_hours') }}</li>
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
                    </nav>
                </div>
            </div>
        </footer>

        <x-site.whatsapp-button :section="$section" />
    </body>
</html>
