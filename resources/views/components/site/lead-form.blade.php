@props([
    'action',
    'offering',
    'priceRanges' => [],
    'interest' => null,
    'section' => null,
    'page' => null,
    'submit' => 'Send',
])

{{-- CMS-3: the site's own enquiry form (FEATURE_CMS). Campaign source is
     added on the server from the visit (CaptureUtm), never from the form. --}}
@php
    $accent = $offering === 'academic' ? 'bg-academic-700 hover:bg-academic-800' : 'bg-tuition-700 hover:bg-tuition-800';
    $input = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-slate-900 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-100';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" novalidate>
    @csrf
    <input type="hidden" name="interest" value="{{ old('interest', $interest) }}">
    <input type="hidden" name="section" value="{{ $section }}">
    <input type="hidden" name="page" value="{{ $page }}">

    @if ($errors->any())
        <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">Please check the highlighted fields.</p>
    @endif

    <div>
        <label for="lead-name" class="text-sm font-medium text-slate-800">{{ $offering === 'tuition' ? 'Parent\'s name' : 'Your name' }}</label>
        <input id="lead-name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="{{ $input }}">
        @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="lead-mobile" class="text-sm font-medium text-slate-800">Mobile (WhatsApp)</label>
            <input id="lead-mobile" name="mobile" type="tel" inputmode="tel" value="{{ old('mobile') }}" required autocomplete="tel-national" placeholder="98765 43210" class="{{ $input }}">
            @error('mobile') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="lead-email" class="text-sm font-medium text-slate-800">Email <span class="font-normal text-slate-500">(optional)</span></label>
            <input id="lead-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" class="{{ $input }}">
            @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="lead-class" class="text-sm font-medium text-slate-800">{{ $offering === 'tuition' ? 'Child\'s class, board and subjects' : 'Level, service and deadline' }}</label>
        <input id="lead-class" name="class_or_service" type="text" value="{{ old('class_or_service') }}" placeholder="{{ $offering === 'tuition' ? 'Class 8, CBSE, Maths and Science' : 'Masters, thesis editing, due 15 March' }}" class="{{ $input }}">
        @error('class_or_service') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="lead-message" class="text-sm font-medium text-slate-800">Anything else? <span class="font-normal text-slate-500">(optional)</span></label>
        <textarea id="lead-message" name="message" rows="3" class="{{ $input }}">{{ old('message') }}</textarea>
        @error('message') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    @if ($priceRanges !== [])
        <fieldset>
            <legend class="text-sm font-medium text-slate-800">{{ $offering === 'tuition' ? 'Which monthly fee suits you?' : 'What budget suits you?' }} <span class="font-normal text-slate-500">(optional)</span></legend>
            <div class="mt-2 space-y-2">
                @foreach ($priceRanges as $range)
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-slate-700">
                        <input type="radio" name="price_range" value="{{ $range }}" @checked(old('price_range') === $range) class="size-4 accent-brand-700">
                        {{ $range }}
                    </label>
                @endforeach
            </div>
            @error('price_range') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </fieldset>
    @endif

    {{-- Honeypot for bots; hidden from people and screen readers. --}}
    <div class="hidden" aria-hidden="true">
        <label for="lead-website">Website</label>
        <input id="lead-website" name="website" type="text" tabindex="-1" autocomplete="off">
    </div>

    <label class="flex items-start gap-3 text-sm text-slate-700">
        <input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent')) class="mt-0.5 size-4 accent-brand-700">
        <span>Send me occasional news and offers by email or WhatsApp. I can unsubscribe at any time.</span>
    </label>

    <p class="text-xs text-slate-500">We use these details only to reply to you. See our <a href="{{ route('privacy') }}" class="underline">privacy policy</a>.</p>

    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg px-5 py-3 font-semibold text-white sm:w-auto {{ $accent }}">{{ $submit }}</button>
</form>
