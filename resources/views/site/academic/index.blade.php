@php($page = 'academic')
@php($text = $content['text'])

<x-site.layout :title="$text['seo_title']" :description="$text['seo_description']" section="academic">
    <section class="bg-gradient-to-b from-academic-50 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-academic-700">{{ $text['eyebrow'] }}</p>
                <h1 class="mt-2 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $content['hero']['title'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $content['hero']['lead'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-site.form-link form="academic" section="academic" :page="$page" interest="General enquiry" variant="academic">{{ $text['quote_button'] }}</x-site.form-link>
                    <a href="#prices" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-800 hover:bg-slate-50">{{ $text['prices_button'] }}</a>
                    @if (config('features.academic_orders'))
                        {{-- M6 (ACA-4): signed-in students place orders on the site. --}}
                        <a href="{{ route('academic.orders.create') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-academic-700 bg-white px-5 py-3 font-semibold text-academic-800 hover:bg-academic-50">{{ $text['order_button'] }}</a>
                    @endif
                </div>
                <p class="mt-4 text-sm text-slate-500">{{ $text['under_18'] }} <a href="{{ route('tuition') }}" class="font-medium text-slate-700 underline">{{ $text['under_18_link'] }}</a></p>
            </div>
            <x-site.hero-image name="academic-services" alt="A thesis with editing marks, a similarity check result, bound copies and a courier parcel" />
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($content['highlights'] as $highlight)
                <div class="rounded-xl border border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">{{ $highlight['title'] }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $highlight['body'] }}</p>
                </div>
            @endforeach
        </div>

        <div id="integrity" class="mt-6 rounded-xl border border-academic-100 bg-academic-50 p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">{{ $text['integrity_title'] }}</h2>
            <p class="mt-2 text-sm text-slate-700">{{ $text['integrity_body'] }}</p>
        </div>
    </section>

    <section id="prices" class="border-y border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <x-site.section-heading :eyebrow="$text['prices_eyebrow']" :title="$text['prices_title']" :lead="$text['prices_lead']" />
            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                @foreach ($content['services'] as $service)
                    <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold text-slate-900">{{ $service['name'] }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $service['body'] }}</p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                            @foreach ($content['levels'] as $index => $level)
                                <div class="rounded-lg bg-slate-50 px-1 py-2">
                                    <dt class="text-xs font-medium text-slate-500">{{ $level }}</dt>
                                    <dd class="mt-0.5 font-bold text-slate-900">{{ $service['prices'][$index] === null ? $text['quote_label'] : \App\Support\Money::format($service['prices'][$index]) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-3 text-sm text-slate-500">
                            {{ $service['turnaround'] }}@if ($service['revisions'] !== null) &middot; {{ $service['revisions'] === 0 ? 'No revisions' : trans_choice(':count free revision|:count free revisions', $service['revisions']) }}@endif
                        </p>
                        <div class="mt-auto pt-5">
                            <x-site.form-link form="academic" section="academic" :page="$page" :interest="$service['name']" variant="outline" class="w-full">{{ $service['prices'][0] === null ? $text['quote_only_button'] : $text['enquire_button'] }}</x-site.form-link>
                        </div>
                    </article>
                @endforeach
            </div>

            <h3 class="mt-12 text-xl font-bold text-slate-900">{{ $text['addons_title'] }}</h3>
            <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($content['addons'] as $addon)
                    <li class="flex items-baseline justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <span class="text-sm text-slate-700">{{ $addon['name'] }}</span>
                        <span class="shrink-0 text-right text-sm"><span class="font-bold text-slate-900">{{ \App\Support\Money::format($addon['price']) }}</span> <span class="text-slate-500">{{ $addon['unit'] }}</span></span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm text-slate-500">{{ $text['address_note'] }}</p>
        </div>
    </section>

    <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <x-site.section-heading :eyebrow="$text['steps_eyebrow']" :title="$text['steps_title']" />
        <div class="mt-8">
            <x-site.steps :steps="$content['steps']" accent="academic" />
        </div>
    </section>

    @if ($content['experts'] !== [])
        <section id="experts" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading :eyebrow="$text['people_eyebrow']" :title="$text['people_title']" />
            <div class="mt-8">
                <x-site.people :people="$content['experts']" />
            </div>
        </section>
    @endif

    @if ($content['testimonials'] !== [])
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading :eyebrow="$text['testimonials_eyebrow']" :title="$text['testimonials_title']" />
            <div class="mt-8">
                <x-site.testimonials :testimonials="$content['testimonials']" />
            </div>
        </section>
    @endif

    <section id="faqs" class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <x-site.section-heading :eyebrow="$text['faqs_eyebrow']" :title="$text['faqs_title']" />
            <div class="mt-8">
                <x-site.faqs :faqs="$content['faqs']" />
            </div>
        </div>
    </section>

    <section id="waitlist" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-6 rounded-2xl bg-academic-800 px-6 py-10 text-white sm:px-10 md:grid-cols-2 md:items-center">
            <div>
                <h2 class="text-2xl font-bold">{{ $text['cta_title'] }}</h2>
                <p class="mt-2 text-academic-100">{{ $text['cta_body'] }}</p>
                <x-site.form-link form="academic" section="academic" :page="$page" interest="General enquiry" variant="light" class="mt-6">{{ $text['cta_button'] }}</x-site.form-link>
            </div>
            <div class="rounded-xl bg-white/10 p-5">
                <h3 class="font-semibold">{{ $content['waitlist']['title'] }}</h3>
                <p class="mt-1 text-sm text-academic-100">{{ $content['waitlist']['body'] }}</p>
                <x-site.form-link form="waitlist" section="academic" :page="$page" :interest="$content['waitlist']['interest']" variant="light" class="mt-4">{{ $text['waitlist_button'] }}</x-site.form-link>
            </div>
        </div>
    </section>

    @if ($articles !== [])
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading :title="$text['guides_title']" />
            <div class="mt-8">
                <x-site.article-list :articles="$articles" />
            </div>
        </section>
    @endif
</x-site.layout>
