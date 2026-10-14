@props(['title', 'lead' => null])

<section class="border-b border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $title }}</h1>
        @if ($lead)
            <p class="mt-4 text-lg text-slate-600">{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
