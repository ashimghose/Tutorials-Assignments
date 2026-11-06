@php
    $section = $job->offering;
    $accent = match ($section) {
        'academic' => 'bg-academic-700 hover:bg-academic-800',
        'music' => 'bg-music-700 hover:bg-music-800',
        default => 'bg-tuition-700 hover:bg-tuition-800',
    };
    $tz = config('app.display_timezone');
    // HIR-6: Google's job listing format, so the job can show in Google's job search.
    $posting = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => $job->title,
        'description' => $job->bodyHtml(),
        'datePosted' => $job->posted_on->toDateString(),
        'validThrough' => $job->closes_on?->copy()->setTimezone($tz)->endOfDay()->toIso8601String(),
        'employmentType' => ['PART_TIME', 'CONTRACTOR'],
        'hiringOrganization' => ['@type' => 'Organization', 'name' => config('app.name'), 'sameAs' => route('home'), 'logo' => url('/apple-touch-icon.png')],
        'jobLocationType' => 'TELECOMMUTE',
        'applicantLocationRequirements' => $job->region === 'India'
            ? ['@type' => 'Country', 'name' => 'India']
            : ['@type' => 'State', 'name' => $job->region],
        'directApply' => true,
    ]);
    $rules = (new \App\Http\Requests\StoreJobApplicationRequest)->fieldRules();
    $check = fn (string $field) => \App\Support\ClientRules::for($field, $rules[$field]);
    $input = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-slate-900 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-100';
    $labels = match ($section) {
        'academic' => ['subjects' => 'Your subjects and research areas', 'subjects_hint' => 'Sociology, research methods, APA formatting', 'link' => 'Link to your CV or LinkedIn profile'],
        'music' => ['subjects' => 'Instruments or singing styles you teach, and your level', 'subjects_hint' => 'Acoustic guitar, Trinity grade 5', 'link' => 'Link to a short video of you playing or singing'],
        default => ['subjects' => 'Subjects, classes and boards you can teach', 'subjects_hint' => 'Maths and Science, classes 8 to 10, CBSE and SEBA', 'link' => 'Link to a short teaching video or your CV'],
    };
    $declaration = $section === 'academic'
        ? 'I understand this role never includes writing work for students to submit as their own. The details I gave are true.'
        : 'I agree to ID and reference checks and police verification, and to never contact students privately. The details I gave are true.';
@endphp

<x-site.layout :title="$job->title" :description="$job->summary" :section="$section">
    <x-slot:head>
        <script type="application/ld+json">
        {!! json_encode($posting, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    </x-slot:head>

    <x-site.page-header :title="$job->title" :lead="$job->summary">
        <p class="mt-4 text-sm text-slate-500">
            <a href="{{ route('careers.index') }}" class="font-medium text-brand-700 hover:underline">All jobs</a>
            &middot; {{ \App\Enums\Offering::from($section)->label() }}
        </p>
    </x-site.page-header>

    <section class="mx-auto max-w-3xl px-4 pt-10 sm:px-6">
        <dl class="grid gap-4 rounded-2xl bg-slate-50 p-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-semibold text-slate-900">Where</dt>
                <dd class="mt-1 text-slate-700">{{ $job->location }}</dd>
            </div>
            <div>
                <dt class="text-sm font-semibold text-slate-900">Languages</dt>
                <dd class="mt-1 text-slate-700">{{ $job->languages }}</dd>
            </div>
            <div>
                <dt class="text-sm font-semibold text-slate-900">Pay</dt>
                <dd class="mt-1 text-slate-700">{{ $job->pay }}</dd>
            </div>
            <div>
                <dt class="text-sm font-semibold text-slate-900">Places</dt>
                <dd class="mt-1 text-slate-700">{{ $job->openings }} &middot; part-time freelance work</dd>
            </div>
            @if ($job->closes_on)
                <div>
                    <dt class="text-sm font-semibold text-slate-900">Apply by</dt>
                    <dd class="mt-1 text-slate-700">{{ $job->closes_on->format('j F Y') }}</dd>
                </div>
            @endif
        </dl>

        <div class="article-body mt-8">
            {!! $job->bodyHtml() !!}
        </div>
    </section>

    <section id="apply" class="mx-auto max-w-3xl scroll-mt-24 px-4 py-12 sm:px-6">
        <h2 class="text-2xl font-bold text-slate-900">Apply for this job</h2>
        <p class="mt-2 text-slate-600">It takes about five minutes. We reply within a few days.</p>

        <form method="POST" action="{{ route('careers.apply', $job) }}" class="mt-6 space-y-5" data-checks novalidate>
            @csrf

            @if ($errors->any())
                <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">Please check the highlighted fields.</p>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div data-field>
                    <label for="job-name" class="text-sm font-medium text-slate-800">Your full name</label>
                    <input id="job-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" {{ $check('name')->class($input) }}>
                    @error('name') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div data-field>
                    <label for="job-city" class="text-sm font-medium text-slate-800">City or town</label>
                    <input id="job-city" name="city" type="text" value="{{ old('city') }}" autocomplete="address-level2" {{ $check('city')->class($input) }}>
                    @error('city') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-phone-field name="mobile" id="job-mobile" label="Mobile (WhatsApp)" :rules="$rules['mobile']" site />
                <div data-field>
                    <label for="job-email" class="text-sm font-medium text-slate-800">Email</label>
                    <input id="job-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" {{ $check('email')->class($input) }}>
                    @error('email') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset>
                <legend class="text-sm font-medium text-slate-800">Languages you can teach in</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (\App\Http\Requests\StoreJobApplicationRequest::LANGUAGES as $language)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-slate-700">
                            <input type="checkbox" name="languages[]" value="{{ $language }}" @checked(in_array($language, (array) old('languages', []), true)) class="size-4 accent-brand-700">
                            {{ $language }}
                        </label>
                    @endforeach
                </div>
                @error('languages') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                @error('languages.*') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </fieldset>

            <div data-field>
                <label for="job-subjects" class="text-sm font-medium text-slate-800">{{ $labels['subjects'] }}</label>
                <input id="job-subjects" name="subjects" type="text" value="{{ old('subjects') }}" placeholder="{{ $labels['subjects_hint'] }}" {{ $check('subjects')->class($input) }}>
                @error('subjects') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div data-field>
                <label for="job-qualifications" class="text-sm font-medium text-slate-800">Qualifications</label>
                <input id="job-qualifications" name="qualifications" type="text" value="{{ old('qualifications') }}" placeholder="{{ $section === 'music' ? 'Trinity grade 5, BA Music' : ($section === 'academic' ? 'PhD Sociology, Delhi University' : 'MSc Maths, B.Ed') }}" {{ $check('qualifications')->class($input) }}>
                @error('qualifications') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div data-field>
                <label for="job-experience" class="text-sm font-medium text-slate-800">Your teaching or guidance experience</label>
                <textarea id="job-experience" name="experience" rows="4" {{ $check('experience')->class($input) }}>{{ old('experience') }}</textarea>
                @error('experience') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div data-field>
                <label for="job-availability" class="text-sm font-medium text-slate-800">When can you work?</label>
                <input id="job-availability" name="availability" type="text" value="{{ old('availability') }}" placeholder="Weekdays 5 to 9 pm, Sunday mornings" {{ $check('availability')->class($input) }}>
                @error('availability') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div data-field>
                <label for="job-link" class="text-sm font-medium text-slate-800">{{ $labels['link'] }} <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="job-link" name="link" type="url" value="{{ old('link') }}" placeholder="https://" {{ $check('link')->class($input) }}>
                <p class="mt-1 text-xs text-slate-500">Google Drive, YouTube, Instagram or LinkedIn. Make sure anyone with the link can open it.</p>
                @error('link') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div data-field>
                <label for="job-message" class="text-sm font-medium text-slate-800">Anything else? <span class="font-normal text-slate-500">(optional)</span></label>
                <textarea id="job-message" name="message" rows="3" {{ $check('message')->class($input) }}>{{ old('message') }}</textarea>
                @error('message') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            {{-- Honeypot for bots; hidden from people and screen readers. --}}
            <div class="hidden" aria-hidden="true">
                <label for="job-website">Website</label>
                <input id="job-website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="declaration" value="1" @checked(old('declaration')) class="mt-0.5 size-4 accent-brand-700">
                    <span>{{ $declaration }}</span>
                </label>
                @error('declaration') <p data-server-error class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <p class="text-xs text-slate-500">We use these details only to consider you for work with us, and delete them after {{ \App\Models\JobApplication::KEEP_MONTHS }} months. See our <a href="{{ route('privacy') }}" class="underline">privacy policy</a>.</p>

            <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg px-5 py-3 font-semibold text-white sm:w-auto {{ $accent }}">Send application</button>
        </form>
    </section>
</x-site.layout>
