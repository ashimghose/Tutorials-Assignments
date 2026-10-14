@props(['articles'])

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($articles as $article)
        <a href="{{ route('resources.show', $article['slug']) }}" class="group rounded-xl border border-slate-200 bg-white p-5 hover:border-slate-300 hover:shadow-sm">
            <p @class([
                'text-xs font-semibold uppercase tracking-wide',
                'text-tuition-700' => $article['offering'] === 'tuition',
                'text-academic-700' => $article['offering'] === 'academic',
            ])>{{ $article['offering'] === 'tuition' ? 'For parents' : 'For college students' }}</p>
            <h3 class="mt-2 font-semibold text-slate-900 group-hover:underline">{{ $article['title'] }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $article['description'] }}</p>
        </a>
    @endforeach
</div>
