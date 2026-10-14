@php($page = 'tuition')

<x-site.layout title="Live online tuition for classes 1 to 12" description="Live online tuition for classes 1 to 12 in small batches of up to 15. {{ implode(' and ', $content['boards']) }} syllabus, monthly fees per subject, free demo class." section="tuition">
    <section class="bg-gradient-to-b from-tuition-50 to-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
            <p class="text-sm font-semibold uppercase tracking-wide text-tuition-700">Tuition for parents of classes 1 to 12</p>
            <h1 class="mt-2 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $content['hero']['title'] }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $content['hero']['lead'] }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <x-site.form-link form="tuition" section="tuition" :page="$page" interest="Free demo class" variant="tuition">Book a free demo class</x-site.form-link>
                <x-site.form-link form="tuition" section="tuition" :page="$page" interest="General enquiry" variant="outline">Ask a question</x-site.form-link>
            </div>
            <p class="mt-4 text-sm text-slate-500">Boards: {{ implode(', ', $content['boards']) }}</p>
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
    </section>

    <section id="fees" class="border-y border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <x-site.section-heading eyebrow="Fees" title="Simple monthly fees" lead="One fee per subject, per child, per month. No admission fee, and the first class is a free demo." />
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($content['plans'] as $plan)
                    <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                        <p class="mt-3"><span class="text-3xl font-bold text-slate-900">{{ \App\Support\Money::format($plan['price']) }}</span></p>
                        <p class="text-sm text-slate-500">{{ $plan['period'] }}</p>
                        <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex gap-2"><span aria-hidden="true" class="text-tuition-700">&#10003;</span> {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-sm text-slate-500"><span class="font-medium text-slate-700">Subjects:</span> {{ implode(', ', $plan['subjects']) }}</p>
                        <div class="mt-auto pt-5">
                            <x-site.form-link form="tuition" section="tuition" :page="$page" :interest="'Free demo: '.$plan['name']" variant="outline" class="w-full">Book a free demo</x-site.form-link>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <x-site.section-heading eyebrow="How it works" title="From enquiry to first class" />
        <div class="mt-8">
            <x-site.steps :steps="$content['steps']" accent="tuition" />
        </div>
    </section>

    @if ($content['teachers'] !== [])
        <section id="teachers" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading eyebrow="Teachers" title="Meet the teachers" />
            <div class="mt-8">
                <x-site.people :people="$content['teachers']" />
            </div>
        </section>
    @endif

    @if ($content['testimonials'] !== [])
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading eyebrow="Parents say" title="What parents tell us" />
            <div class="mt-8">
                <x-site.testimonials :testimonials="$content['testimonials']" />
            </div>
        </section>
    @endif

    <section id="faqs" class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <x-site.section-heading eyebrow="Questions" title="Questions parents ask" />
            <div class="mt-8">
                <x-site.faqs :faqs="$content['faqs']" />
            </div>
        </div>
    </section>

    <section id="waitlist" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-6 rounded-2xl bg-tuition-800 px-6 py-10 text-white sm:px-10 md:grid-cols-2 md:items-center">
            <div>
                <h2 class="text-2xl font-bold">Book a free demo class</h2>
                <p class="mt-2 text-tuition-100">Tell us your child's class, board and subjects, and we will set up a demo with the teacher.</p>
                <x-site.form-link form="tuition" section="tuition" :page="$page" interest="Free demo class" variant="light" class="mt-6">Book a free demo class</x-site.form-link>
            </div>
            <div class="rounded-xl bg-white/10 p-5">
                <h3 class="font-semibold">{{ $content['waitlist']['title'] }}</h3>
                <p class="mt-1 text-sm text-tuition-100">{{ $content['waitlist']['body'] }}</p>
                <x-site.form-link form="waitlist" section="tuition" :page="$page" :interest="$content['waitlist']['interest']" variant="light" class="mt-4">Notify me</x-site.form-link>
            </div>
        </div>
    </section>

    @if ($articles !== [])
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading title="Guides for parents" />
            <div class="mt-8">
                <x-site.article-list :articles="$articles" />
            </div>
        </section>
    @endif
</x-site.layout>
