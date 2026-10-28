<x-site.layout description="Live online tuition for classes 1 to 12 in small batches, and guidance, editing, formatting and printing for Bachelors, Masters and PhD students' research work.">
    <section class="bg-gradient-to-b from-brand-50 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $content['hero']['title'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $content['hero']['lead'] }}</p>
            </div>
            <x-site.hero-image name="home-hero" alt="A live online class on a laptop beside an open book, a graduation cap and bound theses" />
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <div class="grid gap-6 md:grid-cols-2">
            <article class="flex flex-col rounded-2xl border border-tuition-100 bg-tuition-50 p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-tuition-700">{{ $content['offerings']['tuition']['eyebrow'] }}</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $content['offerings']['tuition']['title'] }}</h2>
                <p class="mt-3 text-slate-700">{{ $content['offerings']['tuition']['body'] }}</p>
                <ul class="mt-5 space-y-2 text-slate-700">
                    @foreach ($content['offerings']['tuition']['points'] as $point)
                        <li class="flex gap-2"><span aria-hidden="true" class="text-tuition-700">&#10003;</span> {{ $point }}</li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-6">
                    <a href="{{ route('tuition') }}" class="inline-flex min-h-12 items-center rounded-lg bg-tuition-700 px-5 py-3 font-semibold text-white hover:bg-tuition-800">Explore tuition</a>
                </div>
            </article>

            <article class="flex flex-col rounded-2xl border border-academic-100 bg-academic-50 p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-academic-700">{{ $content['offerings']['academic']['eyebrow'] }}</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $content['offerings']['academic']['title'] }}</h2>
                <p class="mt-3 text-slate-700">{{ $content['offerings']['academic']['body'] }}</p>
                <ul class="mt-5 space-y-2 text-slate-700">
                    @foreach ($content['offerings']['academic']['points'] as $point)
                        <li class="flex gap-2"><span aria-hidden="true" class="text-academic-700">&#10003;</span> {{ $point }}</li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-6">
                    <a href="{{ route('academic') }}" class="inline-flex min-h-12 items-center rounded-lg bg-academic-700 px-5 py-3 font-semibold text-white hover:bg-academic-800">Explore academic services</a>
                </div>
            </article>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <x-site.section-heading title="Free guides" lead="Practical help for parents and students, whether or not you study with us." />
            <div class="mt-8">
                <x-site.article-list :articles="$articles" />
            </div>
            <a href="{{ route('resources.index') }}" class="mt-6 inline-block font-semibold text-brand-700 hover:underline">All guides</a>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="rounded-2xl bg-brand-900 px-6 py-10 text-white sm:px-10">
            <h2 class="text-2xl font-bold">Not sure where to start?</h2>
            <p class="mt-2 max-w-2xl text-brand-100">Message us on WhatsApp and tell us what you need. We reply during office hours: {{ config('site.contact.office_hours') }}.</p>
            <a href="{{ \App\Support\SiteLinks::whatsapp('general') }}" target="_blank" rel="noopener" class="mt-6 inline-flex min-h-12 items-center gap-2 rounded-lg bg-white px-5 py-3 font-semibold text-slate-900 hover:bg-slate-100">
                <x-site.whatsapp-icon class="size-5 text-[#1a8f4a]" /> Message us on WhatsApp
            </a>
        </div>
    </section>
</x-site.layout>
