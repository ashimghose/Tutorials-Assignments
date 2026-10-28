@php($page = 'academic')

<x-site.layout title="Thesis, dissertation and research paper editing, formatting and printing" description="Guidance, editing, proofreading, formatting, similarity checks, printing and delivery for Bachelors, Masters and PhD students' synopses, research papers, theses and dissertations." section="academic">
    <section class="bg-gradient-to-b from-academic-50 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-academic-700">Academic services for students aged 18 and over</p>
                <h1 class="mt-2 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $content['hero']['title'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $content['hero']['lead'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-site.form-link form="academic" section="academic" :page="$page" interest="General enquiry" variant="academic">Get a price for your work</x-site.form-link>
                    <a href="#prices" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-800 hover:bg-slate-50">See the price list</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">In school? Academic services are for students aged 18 or older. See <a href="{{ route('tuition') }}" class="font-medium text-slate-700 underline">Tuition</a> instead.</p>
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
            <h2 class="font-semibold text-slate-900">We help with your work. We don't do it for you.</h2>
            <p class="mt-2 text-sm text-slate-700">We guide, edit, format and check the work you wrote, and teach you how to cite sources properly. We do not write theses, dissertations, papers or assignments for anyone to submit as their own. Universities treat that as misconduct, and under the UGC's 2018 anti-plagiarism regulations it can cost a student their degree.</p>
        </div>
    </section>

    <section id="prices" class="border-y border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <x-site.section-heading eyebrow="Price list" title="Clear prices by level" lead="Fixed prices for standard work. For anything larger or unusual, we send you a quote before you pay." />
            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                @foreach ($content['services'] as $service)
                    <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold text-slate-900">{{ $service['name'] }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $service['body'] }}</p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                            @foreach ($content['levels'] as $index => $level)
                                <div class="rounded-lg bg-slate-50 px-1 py-2">
                                    <dt class="text-xs font-medium text-slate-500">{{ $level }}</dt>
                                    <dd class="mt-0.5 font-bold text-slate-900">{{ $service['prices'][$index] === null ? 'Quote' : \App\Support\Money::format($service['prices'][$index]) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-3 text-sm text-slate-500">
                            {{ $service['turnaround'] }}@if ($service['revisions'] !== null) &middot; {{ $service['revisions'] === 0 ? 'No revisions' : trans_choice(':count free revision|:count free revisions', $service['revisions']) }}@endif
                        </p>
                        <div class="mt-auto pt-5">
                            <x-site.form-link form="academic" section="academic" :page="$page" :interest="$service['name']" variant="outline" class="w-full">{{ $service['prices'][0] === null ? 'Request a quote' : 'Enquire about this' }}</x-site.form-link>
                        </div>
                    </article>
                @endforeach
            </div>

            <h3 class="mt-12 text-xl font-bold text-slate-900">Printing, delivery and express add-ons</h3>
            <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($content['addons'] as $addon)
                    <li class="flex items-baseline justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <span class="text-sm text-slate-700">{{ $addon['name'] }}</span>
                        <span class="shrink-0 text-right text-sm"><span class="font-bold text-slate-900">{{ \App\Support\Money::format($addon['price']) }}</span> <span class="text-slate-500">{{ $addon['unit'] }}</span></span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm text-slate-500">We ask for a delivery address only if you order printing or courier.</p>
        </div>
    </section>

    <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <x-site.section-heading eyebrow="How it works" title="From enquiry to delivery" />
        <div class="mt-8">
            <x-site.steps :steps="$content['steps']" accent="academic" />
        </div>
    </section>

    @if ($content['experts'] !== [])
        <section id="experts" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading eyebrow="Experts" title="Meet the experts" />
            <div class="mt-8">
                <x-site.people :people="$content['experts']" />
            </div>
        </section>
    @endif

    @if ($content['testimonials'] !== [])
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading eyebrow="Students say" title="What students tell us" />
            <div class="mt-8">
                <x-site.testimonials :testimonials="$content['testimonials']" />
            </div>
        </section>
    @endif

    <section id="faqs" class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <x-site.section-heading eyebrow="Questions" title="Questions students ask" />
            <div class="mt-8">
                <x-site.faqs :faqs="$content['faqs']" />
            </div>
        </div>
    </section>

    <section id="waitlist" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-6 rounded-2xl bg-academic-800 px-6 py-10 text-white sm:px-10 md:grid-cols-2 md:items-center">
            <div>
                <h2 class="text-2xl font-bold">Tell us about your work</h2>
                <p class="mt-2 text-academic-100">Share your level, service, page count and deadline, and we will reply with a confirmed price.</p>
                <x-site.form-link form="academic" section="academic" :page="$page" interest="General enquiry" variant="light" class="mt-6">Get a price for your work</x-site.form-link>
            </div>
            <div class="rounded-xl bg-white/10 p-5">
                <h3 class="font-semibold">{{ $content['waitlist']['title'] }}</h3>
                <p class="mt-1 text-sm text-academic-100">{{ $content['waitlist']['body'] }}</p>
                <x-site.form-link form="waitlist" section="academic" :page="$page" :interest="$content['waitlist']['interest']" variant="light" class="mt-4">Notify me</x-site.form-link>
            </div>
        </div>
    </section>

    @if ($articles !== [])
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading title="Guides for students" />
            <div class="mt-8">
                <x-site.article-list :articles="$articles" />
            </div>
        </section>
    @endif
</x-site.layout>
