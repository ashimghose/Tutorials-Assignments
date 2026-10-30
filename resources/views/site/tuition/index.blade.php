@php($page = 'tuition')
@php($text = $content['text'])

<x-site.layout :title="$text['seo_title']" :description="$text['seo_description']" section="tuition">
    <section class="bg-gradient-to-b from-tuition-50 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-tuition-700">{{ $text['eyebrow'] }}</p>
                <h1 class="mt-2 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $content['hero']['title'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $content['hero']['lead'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-site.form-link form="tuition" section="tuition" :page="$page" interest="Free demo class" variant="tuition">{{ $text['demo_button'] }}</x-site.form-link>
                    <x-site.form-link form="tuition" section="tuition" :page="$page" interest="General enquiry" variant="outline">{{ $text['question_button'] }}</x-site.form-link>
                </div>
                <p class="mt-4 text-sm text-slate-500">{{ $text['boards_label'] }}: {{ implode(', ', $content['boards']) }}</p>
            </div>
            <x-site.hero-image name="tuition-live-class" alt="A live online class: a teacher at a whiteboard and students on a video call" />
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
            <x-site.section-heading :eyebrow="$text['fees_eyebrow']" :title="$text['fees_title']" :lead="$text['fees_lead']" />
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
                        <p class="mt-4 text-sm text-slate-500"><span class="font-medium text-slate-700">{{ $text['subjects_label'] }}:</span> {{ implode(', ', $plan['subjects']) }}</p>
                        <div class="mt-auto pt-5">
                            <x-site.form-link form="tuition" section="tuition" :page="$page" :interest="'Free demo: '.$plan['name']" variant="outline" class="w-full">{{ $text['plan_button'] }}</x-site.form-link>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <x-site.section-heading :eyebrow="$text['steps_eyebrow']" :title="$text['steps_title']" />
        <div class="mt-8">
            <x-site.steps :steps="$content['steps']" accent="tuition" />
        </div>
    </section>

    @if ($content['teachers'] !== [])
        <section id="teachers" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <x-site.section-heading :eyebrow="$text['people_eyebrow']" :title="$text['people_title']" />
            <div class="mt-8">
                <x-site.people :people="$content['teachers']" />
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
        <div class="grid gap-6 rounded-2xl bg-tuition-800 px-6 py-10 text-white sm:px-10 md:grid-cols-2 md:items-center">
            <div>
                <h2 class="text-2xl font-bold">{{ $text['cta_title'] }}</h2>
                <p class="mt-2 text-tuition-100">{{ $text['cta_body'] }}</p>
                <x-site.form-link form="tuition" section="tuition" :page="$page" interest="Free demo class" variant="light" class="mt-6">{{ $text['cta_button'] }}</x-site.form-link>
            </div>
            <div class="rounded-xl bg-white/10 p-5">
                <h3 class="font-semibold">{{ $content['waitlist']['title'] }}</h3>
                <p class="mt-1 text-sm text-tuition-100">{{ $content['waitlist']['body'] }}</p>
                <x-site.form-link form="waitlist" section="tuition" :page="$page" :interest="$content['waitlist']['interest']" variant="light" class="mt-4">{{ $text['waitlist_button'] }}</x-site.form-link>
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
