@php
    $heading = match (true) {
        $kind === \App\Enums\LeadKind::Waitlist => 'Join the waitlist',
        $kind === \App\Enums\LeadKind::Demo => 'Book a free demo class',
        $offering === 'academic' => 'Ask about academic services',
        default => 'Ask about tuition',
    };
@endphp

<x-site.layout :title="$heading" description="Send us your details and we will reply on WhatsApp or by phone." :section="$section" robots="noindex">
    <x-site.page-header :title="$heading" lead="Leave your details and we will reply on WhatsApp during office hours." />

    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        @if (filled($interest))
            <p class="mb-6 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700"><span class="font-semibold">About:</span> {{ $interest }}</p>
        @endif

        <x-site.lead-form :action="$action" :offering="$offering" :price-ranges="$priceRanges" :interest="$interest" :section="$section" :page="$page"
            :submit="$kind === \App\Enums\LeadKind::Waitlist ? 'Notify me' : 'Send'" />
    </section>
</x-site.layout>
