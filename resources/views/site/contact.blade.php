@php
    $content = \App\Support\SiteContent::section('contact');
@endphp

<x-site.layout :title="$content['title']" :description="$content['description']">
    <x-site.page-header :title="$content['title']" :lead="$content['lead']" />

    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @php
            // VRT-2: one box per switched-on section, each with its own WhatsApp number.
            $boxStyles = [
                'tuition' => ['box' => 'border-tuition-100 bg-tuition-50', 'button' => 'bg-tuition-700 hover:bg-tuition-800'],
                'academic' => ['box' => 'border-academic-100 bg-academic-50', 'button' => 'bg-academic-700 hover:bg-academic-800'],
                'music' => ['box' => 'border-music-100 bg-music-50', 'button' => 'bg-music-700 hover:bg-music-800'],
            ];
            $boxes = collect(\App\Support\Verticals::enabledKeys())->filter(fn (string $key) => isset($content['cards'][$key]))
                ->mapWithKeys(fn (string $key) => [$key => $content['cards'][$key]])->all();
        @endphp
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($boxes as $key => $card)
                <div class="rounded-xl border p-5 {{ $boxStyles[$key]['box'] }}">
                    <h2 class="font-semibold text-slate-900">{{ $card['title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $card['body'] }}</p>
                    <div class="mt-4 flex flex-col gap-2">
                        <a href="{{ \App\Support\SiteLinks::whatsapp($key) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg px-4 py-3 font-semibold text-white {{ $boxStyles[$key]['button'] }}"><x-site.whatsapp-icon class="size-5" /> {{ $card['whatsapp'] }}</a>
                        <x-site.form-link :form="$key" :section="$key" page="contact" interest="General enquiry" variant="outline">{{ $card['enquire'] }}</x-site.form-link>
                    </div>
                </div>
            @endforeach
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
