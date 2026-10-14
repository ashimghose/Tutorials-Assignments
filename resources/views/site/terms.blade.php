<x-site.layout title="Terms of use" description="The terms for using this website and our tuition and academic services.">
    <x-site.page-header title="Terms of use" lead="Last updated 14 October 2026" />

    {{-- DRAFT for Phase 0. Have a lawyer review this text before launch (spec: Overview). --}}
    <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <p>These terms apply to this website and to the services of {{ config('app.name') }} ("we"). By using the website or our services you agree to them.</p>

        <h2>Tuition</h2>
        <ul>
            <li>Tuition is arranged with a parent or guardian, who pays the fees and is responsible for the child's account.</li>
            <li>Fees are paid monthly, per subject, before classes start. Class times and teachers may change; we tell parents in advance.</li>
            <li>Students must behave respectfully in class. We may remove a student who disrupts classes, and will tell the parent why.</li>
        </ul>

        <h2>Academic services</h2>
        <ul>
            <li>Academic services are only for people aged 18 or older.</li>
            <li>We provide guidance, editing, proofreading, formatting, similarity checking, printing, binding and delivery. We do not write theses, dissertations, papers or assignments for you to submit as your own, and we will refuse requests to do so.</li>
            <li>You are responsible for following your university's rules on academic integrity and for the content you submit.</li>
            <li>Prices, turnaround times and free revisions are as listed on our website or in the quote we send you. Turnaround starts once we have your payment and all the files we need.</li>
        </ul>

        <h2>Payments and refunds</h2>
        <p>Payments are made through a secure payment link or gateway. See our <a href="{{ route('refunds') }}">refund policy</a>.</p>

        <h2>Website content</h2>
        <p>The guides and other content on this website are for general information. They are not a substitute for your school's, teacher's or university's instructions.</p>

        <h2>Liability</h2>
        <p>We take care to deliver good work, but we cannot guarantee exam results, grades or the acceptance of any submission. Our total liability for any service is limited to the amount you paid for it.</p>

        <h2>Governing law</h2>
        <p>These terms are governed by the laws of India.</p>

        <h2>Contact</h2>
        <p>Questions about these terms? <a href="{{ route('contact') }}">Contact us</a>.</p>
    </div>
</x-site.layout>
