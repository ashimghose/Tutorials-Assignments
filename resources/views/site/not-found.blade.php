<x-site.layout title="Page not found" description="This page doesn't exist. Try tuition, academic services or our free guides." robots="noindex">
    <x-site.page-header title="Page not found" lead="We couldn't find that page. It may have moved, or the address may have a typo." />

    <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <p>Try one of these instead:</p>
        <ul>
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="{{ route('tuition') }}">Tuition for classes 1 to 12</a></li>
            <li><a href="{{ route('academic') }}">Academic services</a></li>
            <li><a href="{{ route('contact') }}">Contact us</a></li>
        </ul>
    </div>
</x-site.layout>
