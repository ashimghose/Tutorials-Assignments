@props([
    'name' => 'mobile',
    'countryName' => null,
    'label' => __('Mobile number'),
    'value' => null,
    'country' => null,
    'rules' => [],
    'messages' => [],
    'model' => null,
    'countryModel' => null,
    'id' => null,
    'site' => false,
    'description' => null,
])

{{-- A country code dropdown (searchable, with flags) next to the mobile number,
     like +91 | 98765 43210. Checked in the browser by resources/js/phone-field.js
     and form-checks.js with Google's libphonenumber, and on the server by
     App\Rules\MobileNumber with the PHP port of the same library. --}}
@php
    use App\Support\ClientRules;
    use App\Support\Phone;

    $countryName ??= $name.'_country';
    $id ??= 'phone-'.str_replace(['.', '_'], '-', $model ?? $name);
    $country = Phone::country($model ? $country : old($countryName, $country));
    $value = $model ? $value : old($name, $value);
    $number = ClientRules::attributes($model ?? $name, $rules, $messages);
    $number['data-phone'] = $countryModel ?? $countryName;
    $errorKeys = [$model ?? $name, $countryModel ?? $countryName];

    $labelClass = $site ? 'text-sm font-medium text-slate-800' : 'text-sm font-medium text-zinc-800 select-none';
    $box = $site
        ? 'border border-slate-300 bg-white text-base text-slate-900 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-100'
        : 'border border-zinc-200 border-b-zinc-300/80 bg-white text-base text-zinc-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-brand-600/30 sm:text-sm';
    $height = $site ? 'min-h-11 py-2.5' : 'h-10';
@endphp

<div data-phone-field
     data-countries="{{ implode(' ', Phone::countries()) }}"
     data-preferred="{{ implode(' ', Phone::PREFERRED_COUNTRIES) }}"
     data-country-label="{{ __('Country code') }}"
     data-no-match="{{ __('No country found') }}"
     {{ $attributes->only('class')->merge(['class' => 'block']) }}>
    <label for="{{ $id }}" class="{{ $labelClass }}">{{ $label }}</label>
    @if ($description)
        <p class="mt-1 text-sm text-zinc-500">{{ $description }}</p>
    @endif

    <div class="mt-1 flex gap-2">
        <div class="relative shrink-0">
            <input type="hidden" name="{{ $countryName }}" value="{{ $country }}" data-phone-country @if ($countryModel) wire:model="{{ $countryModel }}" @endif>
            <button type="button" data-phone-toggle aria-haspopup="listbox" aria-expanded="false"
                    aria-label="{{ __('Country code') }}: {{ $country }} {{ Phone::dialCode($country) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 {{ $height }} {{ $box }}">
                <span data-phone-flag aria-hidden="true">{{ Phone::flag($country) }}</span>
                <span data-phone-dial>{{ Phone::dialCode($country) }}</span>
                <svg class="size-4 text-zinc-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
            </button>
            <div data-phone-panel hidden @if ($model) wire:ignore @endif
                 class="absolute start-0 z-30 mt-1 w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-zinc-200 bg-white shadow-lg">
                <div class="border-b border-zinc-200 p-2">
                    <input type="search" data-phone-search autocomplete="off" aria-label="{{ __('Search country or code') }}" placeholder="{{ __('Search country or code') }}"
                           class="block w-full rounded-md border border-zinc-300 px-3 py-2 text-base focus:border-brand-700 focus:outline-none sm:text-sm">
                </div>
                <ul data-phone-list role="listbox" aria-label="{{ __('Country code') }}" class="max-h-64 overflow-y-auto py-1"></ul>
            </div>
        </div>

        <input id="{{ $id }}" type="tel" inputmode="tel" autocomplete="tel-national"
               @if ($model) wire:model="{{ $model }}" @else name="{{ $name }}" value="{{ $value }}" @endif
               placeholder="{{ Phone::example($country) }}"
               {{ $attributes->except('class')->merge($number)->class(['block w-full min-w-0 flex-1 rounded-lg px-3', $height, $box]) }}>
    </div>

    @foreach ($errorKeys as $key)
        @error($key)
            <p data-server-error class="mt-1 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
        @enderror
    @endforeach
</div>
