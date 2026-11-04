{{-- MUS-3: a parent approves an under-18 music learner's account. --}}
<x-site.layout title="Approve music lessons" description="A parent or guardian approves a music learner's account." robots="noindex">
    <x-site.page-header title="Approve {{ $learner->user->name }}'s account" lead="Music lessons for learners under 18 need a parent's or guardian's approval." />

    <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @if ($approved)
            <p data-test="guardian-approved"><strong>Thank you, the account is approved.</strong> {{ $learner->user->name }} can now log in and join a music batch.</p>
            <p>You can withdraw this approval at any time by messaging us on WhatsApp or replying to our email; we will then delete the account.</p>
        @else
            <p>{{ $learner->user->name }} ({{ $learner->user->email }}) signed up for music lessons and gave this email address as their parent or guardian.</p>
            <p>To run the classes we keep their name, email, mobile number, batches, attendance and fee status. We never sell it or use it for advertising. Read our <a href="{{ route('privacy') }}" target="_blank">privacy policy</a> and <a href="{{ route('terms') }}" target="_blank">terms</a>.</p>

            <form method="POST" action="{{ request()->fullUrl() }}" class="not-prose mt-6 space-y-4">
                @csrf
                <label class="flex items-start gap-3 text-base">
                    <input type="checkbox" name="confirm" value="1" class="mt-1 size-4" required>
                    <span>I am {{ $learner->user->name }}'s parent or guardian, and I agree to {{ config('app.name') }} holding these details to provide music lessons.</span>
                </label>
                @error('confirm')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" class="inline-flex min-h-12 items-center rounded-lg bg-brand-800 px-5 py-3 font-semibold text-white hover:bg-brand-900">I agree</button>
            </form>

            <p class="mt-6 text-sm">Not you, or don't agree? Ignore this page. We delete accounts that aren't approved within {{ \App\Actions\Music\SendGuardianConsentRequest::LINK_DAYS }} days.</p>
        @endif
    </div>
</x-site.layout>
