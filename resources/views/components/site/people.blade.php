@props(['people'])

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($people as $person)
        <article class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-semibold text-slate-900">{{ $person['name'] }}</h3>
            <p class="text-sm font-medium text-slate-500">{{ $person['subjects'] }}</p>
            <p class="mt-3 text-sm text-slate-600">{{ $person['bio'] }}</p>
        </article>
    @endforeach
</div>
