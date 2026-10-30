<?php

/*
| Privacy policy (CMS-1). The body is Markdown.
|
| DRAFT: have a lawyer review this text (spec: Privacy).
*/

$name = config('app.name');
$email = config('site.contact.email');
$writeTo = filled($email) ? "at [{$email}](mailto:{$email})" : 'through our [contact page](/contact)';

return [

    'title' => 'Privacy policy',
    'lead' => 'Last updated 14 October 2026',
    'description' => 'How we collect, use and protect your personal data, including children\'s data, under India\'s Digital Personal Data Protection Act.',

    'body' => <<<MD
    This policy explains what personal data {$name} ("we") collects through this website, why, and the choices you have. We follow India's Digital Personal Data Protection Act, 2023.

    ## What we collect

    - **Enquiry and waitlist forms.** When you fill in one of our forms (hosted on Google Forms) we receive what you enter, usually your name, mobile number, email, the class or service you are interested in and your message. The form also records which page and campaign link you came from.
    - **WhatsApp.** If you message us on WhatsApp we receive your number, your profile name and your messages.
    - **Website visits.** We use Cloudflare Web Analytics, which counts page visits without cookies and without identifying you.

    This website does not use advertising trackers or cookies for profiling.

    ## Children

    Our tuition is for children, but we deal with their parents or guardians. Please do not let children fill in our forms or message us themselves. We ask only for a child's first name, class, board and, optionally, school. We do not track, profile or advertise to children.

    ## Academic services

    Academic services are for students aged 18 or older. Files you share with us are seen only by you, the expert working on your order and our admin, and are deleted 90 days after delivery unless you ask us to keep them.

    ## How we use your data

    - To answer your enquiry and arrange a demo class or a quote.
    - To provide the classes or services you pay for.
    - To understand which pages and campaigns bring enquiries, in total, not per person.
    - To send you news or offers, only if you have said yes to that. You can stop them at any time.

    We do not sell your personal data.

    ## Who we share it with

    Only service providers we need to run the business: Google (forms and sheets), WhatsApp, Cloudflare (website hosting and analytics), and, when you pay, our payment gateway. Card and UPI details are handled by the payment gateway and never reach us.

    ## How long we keep it

    Enquiries that do not lead to classes or orders are deleted within 12 months. Records of payments are kept as long as tax law requires.

    ## Your rights

    You can ask us what data we hold about you, ask us to correct or delete it, or withdraw your consent, by contacting us below. Parents can do this for their children.

    ## Contact and grievances

    Write to us {$writeTo} and we will reply within 7 days. If you are not satisfied, you may complain to the Data Protection Board of India.
    MD,

];
