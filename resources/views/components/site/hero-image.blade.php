@props(['name', 'alt'])

{{-- Original illustrations in public/images/illustrations, drawn for this site in the
     Calm Scholar colours. Small SVGs (about 5 KB), so no lazy loading is needed above the fold. --}}
<img src="/images/illustrations/{{ $name }}.svg" alt="{{ $alt }}" width="480" height="360" decoding="async" {{ $attributes->merge(['class' => 'mx-auto w-full max-w-md lg:max-w-none']) }}>
