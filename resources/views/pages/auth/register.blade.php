@php($academic = $accountType === 'academic')

<x-layouts::auth :title="$academic ? __('Sign up for academic services') : __('Parent sign-up')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="$academic ? __('Create your academic account') : __('Create a parent account')"
            :description="$academic
                ? __('For Bachelors, Masters and PhD students aged 18 or over.')
                : __('Parents sign up first, then add each child and create their login.')"
        />

        <nav class="grid grid-cols-2 gap-1 rounded-lg bg-zinc-100 p-1 text-sm font-medium" aria-label="{{ __('Account type') }}">
            <a href="{{ route('register') }}" @class(['rounded-md px-3 py-2 text-center', 'bg-white text-brand-800 shadow-sm' => ! $academic, 'text-zinc-600' => $academic]) @if (! $academic) aria-current="page" @endif>{{ __('Parent (tuition)') }}</a>
            <a href="{{ route('register', ['as' => 'academic']) }}" @class(['rounded-md px-3 py-2 text-center', 'bg-white text-brand-800 shadow-sm' => $academic, 'text-zinc-600' => ! $academic]) @if ($academic) aria-current="page" @endif>{{ __('Academic student') }}</a>
        </nav>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="account_type" value="{{ $academic ? 'academic' : 'parent' }}">

            <!-- Name -->
            <flux:input
                name="name"
                :label="$academic ? __('Your full name') : __('Parent\'s full name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

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
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
