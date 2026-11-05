<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('School students: use the username your parent set up. Everyone else, including music learners: use your email.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (\App\Support\GoogleSignIn::enabled())
            <x-google-button :href="route('google.redirect')" :label="__('Continue with Google')" data-test="google-login" />
            <div class="flex items-center gap-3 text-xs uppercase text-zinc-500" aria-hidden="true">
                <span class="h-px flex-1 bg-zinc-200"></span>{{ __('or') }}<span class="h-px flex-1 bg-zinc-200"></span>
            </div>
        @endif

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6" data-checks novalidate>
            @csrf

            <!-- Email address, or a school student's username -->
            <flux:input
                name="email"
                :label="__('Email or username')"
                :value="old('email')"
                type="text"
                autofocus
                autocomplete="username"
                autocapitalize="none"
                :attributes="\App\Support\ClientRules::for('email', ['required', 'string'])"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                    :attributes="\App\Support\ClientRules::for('password', ['required', 'string'])"
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
