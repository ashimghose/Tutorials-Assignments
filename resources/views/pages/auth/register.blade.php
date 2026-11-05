{{-- AUTH-7: with $google set, someone back from Google answers the same questions without email or password. --}}
@php($google ??= null)
@php($musicOn = \App\Support\Verticals::enabled('music') && config('features.batches'))
@php($accountType = $accountType === 'music' && ! $musicOn ? 'parent' : $accountType)
@php($academic = $accountType === 'academic')
@php($music = $accountType === 'music')
@php($tabs = array_filter([
    'parent' => __('Parent (tuition)'),
    'academic' => __('Academic student'),
    'music' => $musicOn ? __('Music learner') : null,
]))

<x-layouts::auth :title="match ($accountType) { 'academic' => __('Sign up for academic services'), 'music' => __('Sign up for music lessons'), default => __('Parent sign-up') }">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="match ($accountType) { 'academic' => __('Create your academic account'), 'music' => __('Create your music account'), default => __('Create a parent account') }"
            :description="match ($accountType) {
                'academic' => __('For Bachelors, Masters and PhD students aged 18 or over.'),
                'music' => __('For anyone learning music with us. Under 18? A parent approves your account by email.'),
                default => __('Parents sign up first, then add each child and create their login.'),
            }"
        />

        <nav @class(['grid gap-1 rounded-lg bg-zinc-100 p-1 text-sm font-medium', 'grid-cols-3' => count($tabs) === 3, 'grid-cols-2' => count($tabs) === 2]) aria-label="{{ __('Account type') }}">
            @foreach ($tabs as $type => $label)
                <a href="{{ route($google ? 'google.finish' : 'register', $type === 'parent' ? [] : ['as' => $type]) }}" @class(['rounded-md px-2 py-2 text-center', 'bg-white text-brand-800 shadow-sm' => $accountType === $type, 'text-zinc-600' => $accountType !== $type]) @if ($accountType === $type) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($google)
            <p class="rounded-lg bg-brand-50 px-4 py-3 text-sm text-zinc-700">
                {{ __('Signed in with Google as :email. Pick your account type above and answer a few questions to finish.', ['email' => $google['email']]) }}
            </p>
        @elseif (\App\Support\GoogleSignIn::enabled())
            <x-google-button :href="route('google.redirect', ['as' => $accountType])" :label="__('Sign up with Google')" data-test="google-signup" />
            <div class="flex items-center gap-3 text-xs uppercase text-zinc-500" aria-hidden="true">
                <span class="h-px flex-1 bg-zinc-200"></span>{{ __('or use your email') }}<span class="h-px flex-1 bg-zinc-200"></span>
            </div>
        @endif

        <form method="POST" action="{{ route($google ? 'google.finish.store' : 'register.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="account_type" value="{{ $accountType }}">

            <!-- Name -->
            <flux:input
                name="name"
                :label="$academic || $music ? __('Your full name') : __('Parent\'s full name')"
                :value="old('name', $google['name'] ?? '')"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

            @unless ($google)
                <!-- Email Address -->
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
            @endunless

            <!-- Mobile -->
            <flux:input
                name="mobile"
                :label="__('Mobile number')"
                :value="old('mobile')"
                type="tel"
                required
                autocomplete="tel-national"
                inputmode="tel"
                placeholder="98765 43210"
            />

            @unless ($google)
                <!-- Password -->
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
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
                    <flux:checkbox name="adult" value="1" :checked="old('adult')" />
                    <flux:label>{{ __('I am 18 or older') }}</flux:label>
                    <flux:error name="adult" />
                </flux:field>
                <p class="-mt-4 text-sm text-zinc-600">
                    {{ __('Under 18?') }}
                    <flux:link :href="route('tuition')">{{ __('Our Tuition section is for school students') }}</flux:link>.
                </p>
            @endif

            @if ($music)
                {{-- MUS-3: adults agree for themselves; a parent approves an under-18 learner by email. --}}
                <fieldset class="flex flex-col gap-3" x-data="{ age: @js(old('age_group', '')) }">
                    <legend class="mb-2 text-sm font-medium text-zinc-800">{{ __('How old are you?') }}</legend>
                    <label class="flex items-center gap-3 text-sm text-zinc-800">
                        <input type="radio" name="age_group" value="adult" x-model="age" class="size-4 accent-brand-700" required>
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
                                required
                                placeholder="parent@example.com"
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
                <flux:checkbox name="consent" value="1" :checked="old('consent')" />
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
