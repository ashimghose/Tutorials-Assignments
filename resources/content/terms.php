<?php

/*
| Terms of use (CMS-1). The body is Markdown.
|
| DRAFT: have a lawyer review this text (spec: Overview).
*/

$name = config('app.name');

return [

    'title' => 'Terms of use',
    'lead' => 'Last updated 14 October 2026',
    'description' => 'The terms for using this website and our tuition and academic services.',

    'body' => <<<MD
    These terms apply to this website and to the services of {$name} ("we"). By using the website or our services you agree to them.

    ## Tuition

    - Tuition is arranged with a parent or guardian, who pays the fees and is responsible for the child's account.
    - Fees are paid monthly, per subject, before classes start. Class times and teachers may change; we tell parents in advance.
    - Students must behave respectfully in class. We may remove a student who disrupts classes, and will tell the parent why.

    ## Academic services

    - Academic services are only for people aged 18 or older.
    - We provide guidance, editing, proofreading, formatting, similarity checking, printing, binding and delivery. We do not write theses, dissertations, papers or assignments for you to submit as your own, and we will refuse requests to do so.
    - You are responsible for following your university's rules on academic integrity and for the content you submit.
    - Prices, turnaround times and free revisions are as listed on our website or in the quote we send you. Turnaround starts once we have your payment and all the files we need.

    ## Payments and refunds

    Payments are made through a secure payment link or gateway. See our [refund policy](/refund-policy).

    ## Website content

    The guides and other content on this website are for general information. They are not a substitute for your school's, teacher's or university's instructions.

    ## Liability

    We take care to deliver good work, but we cannot guarantee exam results, grades or the acceptance of any submission. Our total liability for any service is limited to the amount you paid for it.

    ## Governing law

    These terms are governed by the laws of India.

    ## Contact

    Questions about these terms? [Contact us](/contact).
    MD,

];
