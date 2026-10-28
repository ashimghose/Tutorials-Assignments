@props([
    'sidebar' => false,
])

{{-- The owner's "Indian Assignments" logo, as on the public site. --}}
<a {{ $attributes->merge(['class' => 'flex items-center px-2 py-1']) }} aria-label="{{ config('app.name') }}">
    <img src="{{ asset('images/brand/indian-assignments-logo.svg') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
</a>
