@php($content = \App\Support\SiteContent::section('contact'))

<x-site.layout :title="$content['title']" :description="$content['description']">
    <x-site.page-header :title="$content['title']" :lead="$content['lead']" />

    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-tuition-100 bg-tuition-50 p-5">
                <h2 class="font-semibold text-slate-900">{{ $content['cards']['tuition']['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $content['cards']['tuition']['body'] }}</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ \App\Support\SiteLinks::whatsapp('tuition') }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-tuition-700 px-4 py-3 font-semibold text-white hover:bg-tuition-800"><x-site.whatsapp-icon class="size-5" /> {{ $content['cards']['tuition']['whatsapp'] }}</a>
                    <x-site.form-link form="tuition" section="tuition" page="contact" interest="General enquiry" variant="outline">{{ $content['cards']['tuition']['enquire'] }}</x-site.form-link>
                </div>
            </div>
            <div class="rounded-xl border border-academic-100 bg-academic-50 p-5">
                <h2 class="font-semibold text-slate-900">{{ $content['cards']['academic']['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $content['cards']['academic']['body'] }}</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ \App\Support\SiteLinks::whatsapp('academic') }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-academic-700 px-4 py-3 font-semibold text-white hover:bg-academic-800"><x-site.whatsapp-icon class="size-5" /> {{ $content['cards']['academic']['whatsapp'] }}</a>
                    <x-site.form-link form="academic" section="academic" page="contact" interest="General enquiry" variant="outline">{{ $content['cards']['academic']['enquire'] }}</x-site.form-link>
                </div>
            </div>
        </div>

        <dl class="mt-10 space-y-4 text-slate-700">
            <div>
                <dt class="font-semibold text-slate-900">Office hours</dt>
                <dd>{{ config('site.contact.office_hours') }}. {{ $content['office_hours_note'] }}</dd>
            </div>
            @if (filled(config('site.contact.email')))
                <div>
                    <dt class="font-semibold text-slate-900">Email</dt>
                    <dd><a href="mailto:{{ config('site.contact.email') }}" class="text-brand-700 underline">{{ config('site.contact.email') }}</a></dd>
                </div>
            @endif
            @if (filled(config('site.contact.city')))
                <div>
                    <dt class="font-semibold text-slate-900">Based in</dt>
                    <dd>{{ config('site.contact.city') }}</dd>
                </div>
            @endif
        </dl>
    </section>
</x-site.layout>
