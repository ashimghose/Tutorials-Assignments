{{-- AUTH-7: a new Google sign-in from the login page first says who they are. --}}
@php($options = array_filter([
    'academic' => [__('I am a college or university student'), __('Academic services: assignments, theses, editing and printing. For ages 18 and over.')],
    'parent' => [__('I am a parent'), __('Tuition for my child in classes 1 to 12. You add your child after signing up.')],
    'music' => $musicOpen ? [__('I want to learn music'), __('Singing or an instrument. Under 18? Your parent approves by email.')] : null,
]))

<x-layouts::auth :title="__('Who are you?')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Who are you?')"
            :description="__('Signed in with Google as :email. Choose one to finish creating your account.', ['email' => $google['email']])"
        />

        <div class="flex flex-col gap-3">
            @foreach ($options as $type => [$title, $text])
                <a href="{{ route('google.finish', ['as' => $type]) }}" data-test="choose-{{ $type }}"
                   class="block rounded-lg border border-zinc-300 bg-white px-4 py-3 hover:border-brand-600 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                    <span class="block font-medium text-zinc-900">{{ $title }}</span>
                    <span class="mt-1 block text-sm text-zinc-600">{{ $text }}</span>
                </a>
            @endforeach
        </div>

        <p class="text-center text-sm text-zinc-600">
            {{ __('School student? Your parent signs up and creates your login.') }}
        </p>
    </div>
</x-layouts::auth>
