@props(['steps', 'accent' => 'brand'])

<ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($steps as $step)
        <li class="rounded-xl border border-slate-200 bg-white p-5">
            <span @class([
                'grid size-9 place-items-center rounded-full text-sm font-bold',
                'bg-tuition-100 text-tuition-800' => $accent === 'tuition',
                'bg-academic-100 text-academic-800' => $accent === 'academic',
                'bg-brand-100 text-brand-800' => $accent === 'brand',
            ])>{{ $loop->iteration }}</span>
            <h3 class="mt-3 font-semibold text-slate-900">{{ $step['title'] }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ $step['body'] }}</p>
        </li>
    @endforeach
</ol>
