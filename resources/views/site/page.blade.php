@php($content = \App\Support\SiteContent::section($key))

<x-site.layout :title="$content['title']" :description="$content['description']">
    <x-site.page-header :title="$content['title']" :lead="$content['lead']" />

    <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
        {!! \Illuminate\Support\Str::markdown($content['body'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</x-site.layout>
