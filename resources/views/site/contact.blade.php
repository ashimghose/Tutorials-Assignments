<x-site.layout title="Contact us" description="Contact us on WhatsApp or by email about tuition for classes 1 to 12 or academic services for college students.">
    <x-site.page-header title="Contact us" lead="The quickest way to reach us is WhatsApp. Pick the topic so we can route your message to the right person." />

    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-tuition-100 bg-tuition-50 p-5">
                <h2 class="font-semibold text-slate-900">Tuition, classes 1 to 12</h2>
                <p class="mt-1 text-sm text-slate-600">For parents asking about classes, fees or a free demo.</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ \App\Support\SiteLinks::whatsapp('tuition') }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-tuition-700 px-4 py-3 font-semibold text-white hover:bg-tuition-800"><x-site.whatsapp-icon class="size-5" /> WhatsApp about tuition</a>
                    <x-site.form-link form="tuition" section="tuition" page="contact" interest="General enquiry" variant="outline">Send an enquiry</x-site.form-link>
                </div>
            </div>
            <div class="rounded-xl border border-academic-100 bg-academic-50 p-5">
                <h2 class="font-semibold text-slate-900">Academic services</h2>
                <p class="mt-1 text-sm text-slate-600">For students aged 18 or older asking about a service or a price.</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ \App\Support\SiteLinks::whatsapp('academic') }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-academic-700 px-4 py-3 font-semibold text-white hover:bg-academic-800"><x-site.whatsapp-icon class="size-5" /> WhatsApp about academic work</a>
                    <x-site.form-link form="academic" section="academic" page="contact" interest="General enquiry" variant="outline">Send an enquiry</x-site.form-link>
                </div>
            </div>
        </div>

        <dl class="mt-10 space-y-4 text-slate-700">
            <div>
                <dt class="font-semibold text-slate-900">Office hours</dt>
                <dd>{{ config('site.contact.office_hours') }}. Messages sent outside these hours are answered the next working day.</dd>
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
