<x-site.layout title="Privacy policy" description="How we collect, use and protect your personal data, including children's data, under India's Digital Personal Data Protection Act.">
    <x-site.page-header title="Privacy policy" lead="Last updated 14 October 2026" />

    {{-- DRAFT for Phase 0. Have a lawyer review this text before launch (spec: Privacy). --}}
    <div class="article-body mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <p>This policy explains what personal data {{ config('app.name') }} ("we") collects through this website, why, and the choices you have. We follow India's Digital Personal Data Protection Act, 2023.</p>

        <h2>What we collect</h2>
        <ul>
            <li><strong>Enquiry and waitlist forms.</strong> When you fill in one of our forms (hosted on Google Forms) we receive what you enter, usually your name, mobile number, email, the class or service you are interested in and your message. The form also records which page and campaign link you came from.</li>
            <li><strong>WhatsApp.</strong> If you message us on WhatsApp we receive your number, your profile name and your messages.</li>
            <li><strong>Website visits.</strong> We use Cloudflare Web Analytics, which counts page visits without cookies and without identifying you.</li>
        </ul>
        <p>This website does not use advertising trackers or cookies for profiling.</p>

        <h2>Children</h2>
        <p>Our tuition is for children, but we deal with their parents or guardians. Please do not let children fill in our forms or message us themselves. We ask only for a child's first name, class, board and, optionally, school. We do not track, profile or advertise to children.</p>

        <h2>Academic services</h2>
        <p>Academic services are for students aged 18 or older. Files you share with us are seen only by you, the expert working on your order and our admin, and are deleted 90 days after delivery unless you ask us to keep them.</p>

        <h2>How we use your data</h2>
        <ul>
            <li>To answer your enquiry and arrange a demo class or a quote.</li>
            <li>To provide the classes or services you pay for.</li>
            <li>To understand which pages and campaigns bring enquiries, in total, not per person.</li>
            <li>To send you news or offers, only if you have said yes to that. You can stop them at any time.</li>
        </ul>
        <p>We do not sell your personal data.</p>

        <h2>Who we share it with</h2>
        <p>Only service providers we need to run the business: Google (forms and sheets), WhatsApp, Cloudflare (website hosting and analytics), and, when you pay, our payment gateway. Card and UPI details are handled by the payment gateway and never reach us.</p>

        <h2>How long we keep it</h2>
        <p>Enquiries that do not lead to classes or orders are deleted within 12 months. Records of payments are kept as long as tax law requires.</p>

        <h2>Your rights</h2>
        <p>You can ask us what data we hold about you, ask us to correct or delete it, or withdraw your consent, by contacting us below. Parents can do this for their children.</p>

        <h2>Contact and grievances</h2>
        <p>
            Write to us
            @if (filled(config('site.contact.email')))
                at <a href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>
            @else
                through our <a href="{{ route('contact') }}">contact page</a>
            @endif
            and we will reply within 7 days. If you are not satisfied, you may complain to the Data Protection Board of India.
        </p>
    </div>
</x-site.layout>
