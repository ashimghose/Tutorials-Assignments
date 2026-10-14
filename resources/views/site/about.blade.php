<x-site.layout title="About us" description="Who we are: live online tuition for school children and academic guidance, editing and printing for college students.">
    <x-site.page-header title="About us" lead="We help school children learn and college students finish their research well." />

    <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
        {{-- PLACEHOLDER: replace with your own story, team and city. --}}
        <p>{{ config('app.name') }} offers two services from one team.</p>
        <h2>Tuition for classes 1 to 12</h2>
        <p>Live online classes in small batches of up to 15 students, so every child gets attention. Parents choose the subjects, pay a monthly fee and stay informed about every class, notice and message.</p>
        <h2>Academic services for college students</h2>
        <p>Bachelors, Masters and PhD students come to us for guidance on their synopsis, editing and proofreading, formatting to their university's style, similarity checks, and printing and binding with delivery. We work on the student's own writing and help them learn to do it well; we never supply work for them to submit as their own.</p>
        <h2>How we work</h2>
        <ul>
            <li>Clear prices, published on this site.</li>
            <li>Parents, not children, are our contact for tuition.</li>
            <li>Your files and personal details are kept private.</li>
            <li>Honest advice, even when it means telling you we are not the right fit.</li>
        </ul>
        <p>Questions? <a href="{{ route('contact') }}">Get in touch</a>.</p>
    </div>
</x-site.layout>
