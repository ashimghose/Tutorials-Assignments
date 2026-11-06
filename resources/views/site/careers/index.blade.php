@php
    $groups = $jobs->groupBy('offering');
    $order = ['academic', 'tuition', 'music'];
    $badge = fn (string $offering) => match ($offering) {
        'academic' => 'bg-academic-50 text-academic-800',
        'music' => 'bg-music-50 text-music-800',
        default => 'bg-tuition-50 text-tuition-800',
    };
@endphp

<x-site.layout title="Careers: teach with us" description="Part-time online jobs for academic guidance experts, school tuition teachers and music teachers. Read the job descriptions and apply online.">
    <x-site.page-header title="Teach with us" lead="We are hiring part-time academic guidance experts, school tuition teachers and music teachers. All work is online, from home, and paid monthly." />

    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        @if ($jobs->isEmpty())
            <p class="rounded-lg bg-slate-50 px-4 py-6 text-slate-700">There are no open jobs right now. Please check again soon, or <a href="{{ route('contact') }}" class="font-medium text-brand-700 underline">contact us</a>.</p>
        @endif

        @foreach ($order as $offering)
            @continue(! $groups->has($offering))
            <h2 class="mt-10 text-xl font-bold text-slate-900 first:mt-0">{{ \App\Enums\Offering::from($offering)->label() }}</h2>
            <ul class="mt-4 space-y-4">
                @foreach ($groups[$offering] as $job)
                    <li>
                        <a href="{{ route('careers.show', $job) }}" class="block rounded-xl border border-slate-200 p-5 hover:border-brand-700 hover:shadow-sm">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="text-lg font-semibold text-slate-900">{{ $job->title }}</span>
                                @if ($job->openings > 1)
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge($job->offering) }}">{{ $job->openings }} places</span>
                                @endif
                            </span>
                            <span class="mt-2 block text-slate-600">{{ $job->summary }}</span>
                            <span class="mt-3 block text-sm text-slate-500">{{ $job->location }} &middot; {{ $job->languages }}</span>
                            <span class="mt-3 inline-block font-semibold text-brand-700">Read more and apply &rarr;</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach

        <div class="mt-12 rounded-2xl bg-slate-50 px-6 py-8">
            <h2 class="text-xl font-bold text-slate-900">How we hire</h2>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-slate-700">
                <li>Apply online for the job that suits you.</li>
                <li>We call you within a few days.</li>
                <li>You give a short <strong>paid</strong> demo class or test task.</li>
                <li>We check your ID and references. Anyone teaching children also needs police verification.</li>
                <li>We sign a simple freelance agreement, and you start.</li>
            </ol>
            @if (config('features.accounts'))
                {{-- HIR-8: teachers can also make an account now; we approve it after the checks. --}}
                <p class="mt-6 text-slate-700">Want to set up your account now? <a href="{{ route('register', ['as' => 'teacher']) }}" class="font-semibold text-brand-700 underline">Sign up as a teacher</a>. We approve it after the same checks.</p>
            @endif
        </div>
    </section>
</x-site.layout>
