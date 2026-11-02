@props(['measurementId'])

{{-- Google Analytics sets cookies, so it loads only after the visitor taps "Allow" (resources/js/consent.js). --}}
<div id="cookie-consent" data-ga-id="{{ $measurementId }}" hidden
     role="region" aria-label="Cookie choice"
     class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white px-4 py-4 shadow-[0_-4px_16px_rgba(15,23,42,0.08)] sm:px-6">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-700">
            May we use Google Analytics cookies to see which pages help visitors? We don't use them for ads.
            <a href="{{ route('privacy') }}" class="font-medium text-brand-800 underline underline-offset-2">Privacy policy</a>
        </p>
        <div class="flex shrink-0 gap-2">
            <button type="button" data-consent="denied" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">No, thanks</button>
            <button type="button" data-consent="granted" class="rounded-md bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Allow</button>
        </div>
    </div>
</div>
