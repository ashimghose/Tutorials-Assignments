@props(['section' => 'general'])

{{-- CHAT-6: floating click-to-chat button; the message differs per section. --}}
<a href="{{ \App\Support\SiteLinks::whatsapp($section) }}"
   target="_blank" rel="noopener"
   data-whatsapp="{{ $section }}"
   class="fixed bottom-4 right-4 z-40 flex items-center gap-2 rounded-full bg-[#1a8f4a] py-3 pl-3 pr-4 font-semibold text-white shadow-lg hover:bg-[#157a3e] sm:bottom-6 sm:right-6">
    <x-site.whatsapp-icon class="size-6" />
    <span class="text-sm">Chat on WhatsApp</span>
</a>
