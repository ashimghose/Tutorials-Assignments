<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
{{-- Log-in pages and the portal stay out of Google; only the marketing pages are indexed.
     Links stay followable so site checkers don't flag every page as nofollow. --}}
<meta name="robots" content="noindex" />
<meta name="description" content="{{ config('app.name', 'Laravel') }}: your account for tuition, academic services and music lessons." />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
