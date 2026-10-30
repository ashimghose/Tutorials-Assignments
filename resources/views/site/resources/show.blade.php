@php($section = $article['offering'])

<x-site.layout :title="$article['title']" :description="$article['description']" :section="$section" type="article">
    <x-slot:head>
        <meta property="article:published_time" content="{{ $article['published'] }}">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $article['description'],
            'datePublished' => $article['published'],
            'image' => url('/images/social/share.png'),
            'mainEntityOfPage' => route('resources.show', $article['slug']),
            'author' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => route('home')],
            'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => url('/apple-touch-icon.png')]],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    </x-slot:head>
    <article>
        <x-site.page-header :title="$article['title']" :lead="$article['description']">
            <p class="mt-4 text-sm text-slate-500">
                <a href="{{ route('resources.index') }}" class="font-medium text-brand-700 hover:underline">Guides</a>
                &middot; <time datetime="{{ $article['published'] }}">{{ \Illuminate\Support\Carbon::parse($article['published'])->format('j F Y') }}</time>
            </p>
        </x-site.page-header>

        <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
            {!! $article['html'] !!}
        </div>
    </article>

    <section class="mx-auto max-w-3xl px-4 pb-16 sm:px-6">
        <div @class([
            'rounded-2xl px-6 py-8 text-white',
            'bg-tuition-800' => $section === 'tuition',
            'bg-academic-800' => $section === 'academic',
        ])>
            @if ($section === 'tuition')
                <h2 class="text-xl font-bold">Want a teacher's help?</h2>
                <p class="mt-2 text-tuition-100">Small live online batches for classes 1 to 12, and the first class is a free demo.</p>
                <x-site.form-link form="tuition" section="tuition" :page="'resources/'.$article['slug']" interest="Free demo class" variant="light" class="mt-5">Book a free demo class</x-site.form-link>
            @else
                <h2 class="text-xl font-bold">Want an expert to check your work?</h2>
                <p class="mt-2 text-academic-100">Guidance, editing, formatting and similarity checks for your own synopsis, paper or thesis.</p>
                <x-site.form-link form="academic" section="academic" :page="'resources/'.$article['slug']" :interest="'Guide: '.$article['title']" variant="light" class="mt-5">Get a price for your work</x-site.form-link>
            @endif
        </div>
    </section>
</x-site.layout>
