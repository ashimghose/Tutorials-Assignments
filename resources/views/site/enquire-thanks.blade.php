<x-site.layout title="Thank you" description="We have your details and will reply soon." :section="$section" robots="noindex">
    <x-site.page-header title="Thank you, we have your details" lead="We reply on WhatsApp during office hours, usually the same day.">
        <p class="mt-4 text-slate-600">{{ config('site.contact.office_hours') }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route($section) }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-800 hover:bg-slate-50">Back to {{ $section === 'academic' ? 'academic services' : 'tuition' }}</a>
            <a href="{{ \App\Support\SiteLinks::whatsapp($section) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-brand-800 px-5 py-3 font-semibold text-white hover:bg-brand-900"><x-site.whatsapp-icon class="size-5" /> Message us now</a>
        </div>
    </x-site.page-header>
</x-site.layout>
