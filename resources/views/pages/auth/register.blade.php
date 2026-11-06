{{-- AUTH-7: with $google set, someone back from Google answers the same questions without email or password. --}}
@php($google ??= null)
@php($musicOn = \App\Support\Verticals::enabled('music') && config('features.batches'))
@php($accountType = $accountType === 'music' && ! $musicOn ? 'parent' : $accountType)
@php($academic = $accountType === 'academic')
@php($music = $accountType === 'music')
@php($teacher = $accountType === 'teacher')
@php($signUp = app(\App\Actions\Fortify\CreateNewUser::class))
@php($rules = $signUp->rules($accountType, $music, (bool) $google))
@php($messages = $signUp->messages($accountType))
@php($check = fn (string $field) => \App\Support\ClientRules::for($field, $rules[$field] ?? [], $messages))
@php($tabs = array_filter([
    'parent' => __('Parent (tuition)'),
    'academic' => __('Academic student'),
    'music' => $musicOn ? __('Music learner') : null,
    'teacher' => __('Teacher'),
]))

<x-layouts::auth :title="match ($accountType) { 'academic' => __('Sign up for academic services'), 'music' => __('Sign up for music lessons'), 'teacher' => __('Teacher sign-up'), default => __('Parent sign-up') }">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="match ($accountType) { 'academic' => __('Create your academic account'), 'music' => __('Create your music account'), 'teacher' => __('Create your teacher account'), default => __('Create a parent account') }"
            :description="match ($accountType) {
                'academic' => __('For Bachelors, Masters and PhD students aged 18 or over.'),
                'music' => __('For anyone learning music with us. Under 18? A parent approves your account by email.'),
                'teacher' => __('For tuition teachers, music teachers and academic guidance experts. We approve your account after ID, reference and safety checks.'),
                default => __('Parents sign up first, then add each child and create their login.'),
            }"
        />

        <nav @class(['grid gap-1 rounded-lg bg-zinc-100 p-1 text-sm font-medium', 'grid-cols-2 sm:grid-cols-4' => count($tabs) === 4, 'grid-cols-3' => count($tabs) === 3, 'grid-cols-2' => count($tabs) === 2]) aria-label="{{ __('Account type') }}">
            @foreach ($tabs as $type => $label)
                <a href="{{ $google ? route('google.finish', ['as' => $type]) : route('register', $type === 'parent' ? [] : ['as' => $type]) }}" @class(['rounded-md px-2 py-2 text-center', 'bg-white text-brand-800 shadow-sm' => $accountType === $type, 'text-zinc-600' => $accountType !== $type]) @if ($accountType === $type) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($google)
            <p class="rounded-lg bg-brand-50 px-4 py-3 text-sm text-zinc-700">
                {{ __('Signed in with Google as :email. Answer a few questions to finish.', ['email' => $google['email']]) }}
            </p>
        @elseif (\App\Support\GoogleSignIn::enabled())
            <x-google-button :href="route('google.redirect', ['as' => $accountType])" :label="__('Sign up with Google')" data-test="google-signup" />
            <div class="flex items-center gap-3 text-xs uppercase text-zinc-500" aria-hidden="true">
                <span class="h-px flex-1 bg-zinc-200"></span>{{ __('or use your email') }}<span class="h-px flex-1 bg-zinc-200"></span>
            </div>
        @endif

        <form method="POST" action="{{ route($google ? 'google.finish.store' : 'register.store') }}" class="flex flex-col gap-6" data-checks novalidate>
            @csrf
            <input type="hidden" name="account_type" value="{{ $accountType }}">

            <!-- Name -->
            <flux:input
                name="name"
                :label="$academic || $music || $teacher ? __('Your full name') : __('Parent\'s full name')"
                :value="old('name', $google['name'] ?? '')"
                type="text"
                autofocus
                autocomplete="name"
                :attributes="$check('name')"
            />

            @unless ($google)
                <!-- Email Address -->
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    autocomplete="email"
                    placeholder="email@example.com"
                    :attributes="$check('email')"
                />
            @endunless

            <!-- Mobile -->
            <x-phone-field name="mobile" :label="__('Mobile number')" :rules="$rules['mobile']" :messages="$messages" />

            @unless ($google)
                <!-- Password -->
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    :attributes="$check('password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />

                <!-- Confirm Password -->
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
            @endunless

            @if ($academic)
                <flux:field variant="inline">
                    <flux:checkbox name="adult" value="1" :checked="old('adult')" :attributes="$check('adult')" />
                    <flux:label>{{ __('I am 18 or older') }}</flux:label>
                    <flux:error name="adult" />
                </flux:field>
                <p class="-mt-4 text-sm text-zinc-600">
                    {{ __('Under 18?') }}
                    <flux:link :href="route('tuition')">{{ __('Our Tuition section is for school students') }}</flux:link>.
                </p>
            @endif

            @if ($teacher)
                {{-- HIR-8: the account waits for the admin's approval before any work. --}}
                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-1 text-sm font-medium text-zinc-800">{{ __('Where do you want to teach?') }}</legend>
                    @foreach (\App\Enums\Offering::sections() as $section)
                        <label class="flex items-center gap-3 text-sm text-zinc-800">
                            <input type="checkbox" name="offerings[]" value="{{ $section->value }}" @checked(in_array($section->value, (array) old('offerings', []), true)) class="size-4 accent-brand-700">
                            {{ match ($section->value) { 'academic' => __('Academic guidance, editing and formatting'), 'music' => __('Music lessons'), default => __('School tuition, classes 1 to 12') } }}
                        </label>
                    @endforeach
                    <flux:error name="offerings" />
                    <flux:error name="offerings.*" />
                </fieldset>

                <flux:input
                    name="subjects"
                    :label="__('What do you teach?')"
                    :description="__('Separate with commas.')"
                    :value="old('subjects')"
                    type="text"
                    :placeholder="__('Maths, Science, Guitar, Thesis editing')"
                    :attributes="$check('subjects')"
                />

                <flux:field variant="inline">
                    <flux:checkbox name="adult" value="1" :checked="old('adult')" :attributes="$check('adult')" />
                    <flux:label>{{ __('I am 18 or older') }}</flux:label>
                    <flux:error name="adult" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:checkbox name="teacher_rules" value="1" :checked="old('teacher_rules')" :attributes="$check('teacher_rules')" />
                    <flux:label>{{ __('I agree to ID and reference checks (and police verification to teach children), to never contact students privately, and to never write work for students to submit as their own.') }}</flux:label>
                    <flux:error name="teacher_rules" />
                </flux:field>
            @endif

            @if ($music)
                {{-- MUS-3: adults agree for themselves; a parent approves an under-18 learner by email. --}}
                <fieldset class="flex flex-col gap-3" x-data="{ age: @js(old('age_group', '')) }">
                    <legend class="mb-2 text-sm font-medium text-zinc-800">{{ __('How old are you?') }}</legend>
                    <label class="flex items-center gap-3 text-sm text-zinc-800">
                        <input type="radio" name="age_group" value="adult" x-model="age" class="size-4 accent-brand-700" {{ $check('age_group') }}>
                        {{ __('I am 18 or older') }}
                    </label>
                    <label class="flex items-center gap-3 text-sm text-zinc-800">
                        <input type="radio" name="age_group" value="minor" x-model="age" class="size-4 accent-brand-700">
                        {{ __('I am under 18') }}
                    </label>
                    <flux:error name="age_group" />

                    <template x-if="age === 'adult'">
                        <input type="hidden" name="adult" value="1">
                    </template>

                    <template x-if="age === 'minor'">
                        <div class="flex flex-col gap-2">
                            <flux:input
                                name="guardian_email"
                                :label="__('Parent\'s or guardian\'s email')"
                                :value="old('guardian_email')"
                                type="email"
                                placeholder="parent@example.com"
                                :attributes="\App\Support\ClientRules::for('guardian_email', $signUp->rules('music', true)['guardian_email'], $messages)"
                            />
                            <p class="text-sm text-zinc-600">{{ __('We email them a link to approve your account. You can join a batch once they agree.') }}</p>
                        </div>
                    </template>
                    @error('adult')
                        <flux:error name="adult" />
                    @enderror
                </fieldset>
            @endif

            <flux:field variant="inline">
                <flux:checkbox name="consent" value="1" :checked="old('consent')" :attributes="$check('consent')" />
                <flux:label>
                    <span>{{ __('I agree to the') }} <flux:link :href="route('privacy')" target="_blank">{{ __('privacy policy') }}</flux:link> {{ __('and') }} <flux:link :href="route('terms')" target="_blank">{{ __('terms') }}</flux:link>.</span>
                </flux:label>
                <flux:error name="consent" />
            </flux:field>

            {{-- NOT-3: marketing email only with an explicit opt-in, unticked by default. --}}
            <flux:field variant="inline">
                <flux:checkbox name="marketing" value="1" :checked="old('marketing')" />
                <flux:label>{{ __('Send me occasional news and offers by email. I can unsubscribe at any time.') }}</flux:label>
            </flux:field>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ $google ? __('Finish and create account') : __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
