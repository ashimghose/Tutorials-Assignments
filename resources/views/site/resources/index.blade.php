@php($text = \App\Support\SiteContent::section('home')['text'])

<x-site.layout title="Free guides for parents and students" description="Free guides on board exam preparation, synopsis structure, citation styles and more, for parents of school children and college students.">
    <x-site.page-header :title="$text['guides_title']" :lead="$text['guides_page_lead']" />

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <x-site.article-list :articles="$articles" />
    </section>
</x-site.layout>
