<x-layouts::auth :title="__('Set your password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome, :name', ['name' => $user->name])" :description="__('Choose a password to finish setting up your teacher account.')" />

        <form method="POST" action="{{ request()->fullUrl() }}" class="flex flex-col gap-6">
            @csrf

            <flux:input :label="__('Email address')" :value="$user->email" type="email" disabled />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autofocus
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full" data-test="accept-invitation-button">
                {{ __('Set password and continue') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
