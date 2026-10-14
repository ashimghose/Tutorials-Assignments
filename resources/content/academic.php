<?php

use App\Support\Money;

/*
| Academic section content (MKT-1). Edit the text here until M4 moves it into
| the admin panel. Prices are whole paise.
|
| Scope guardrail: we sell guidance, editing, formatting, similarity checking,
| printing and delivery. Never offer finished work for a student to submit as
| their own (UGC 2018 anti-plagiarism rules).
|
| PLACEHOLDER: every price, turnaround and revision count below is an example.
| Replace them with your real price list (and have a lawyer review the service
| wording) before the site goes live.
*/

return [

    'hero' => [
        'title' => 'Guidance, editing and printing for your thesis, dissertation or research paper',
        'lead' => 'For Bachelors, Masters and PhD students in any stream. We help you structure, polish, format and check your own work, then print, bind and deliver it.',
    ],

    'highlights' => [
        ['title' => 'Your work, made stronger', 'body' => 'Structure guidance, language editing and proofreading on the text you wrote.'],
        ['title' => 'Formatted to your university\'s rules', 'body' => 'Margins, headings, tables, figures, citations and reference lists in the style your university asks for.'],
        ['title' => 'Printed and delivered', 'body' => 'Spiral, soft or hard bound copies, couriered to your door.'],
    ],

    'levels' => ['Bachelors', 'Masters', 'PhD'],

    // PLACEHOLDER prices, by level: [Bachelors, Masters, PhD]. null = quote only.
    'services' => [
        [
            'name' => 'Synopsis guidance and review',
            'body' => 'Feedback on your research question, objectives, methodology and structure, with a marked-up copy and a call to talk it through.',
            'prices' => [Money::fromRupees(1500), Money::fromRupees(2500), Money::fromRupees(4000)],
            'turnaround' => '5 working days',
            'revisions' => 1,
        ],
        [
            'name' => 'Research paper editing',
            'body' => 'Language editing and proofreading, with comments on clarity, flow and structure, ready for submission to a journal or conference.',
            'prices' => [Money::fromRupees(2000), Money::fromRupees(3000), Money::fromRupees(5000)],
            'turnaround' => '5 working days',
            'revisions' => 1,
        ],
        [
            'name' => 'Thesis or dissertation editing and proofreading',
            'body' => 'Chapter-by-chapter language editing, consistency checks and comments on argument and structure. Priced for up to 100 pages; longer work is quoted.',
            'prices' => [Money::fromRupees(5000), Money::fromRupees(8000), Money::fromRupees(15000)],
            'turnaround' => '10 working days',
            'revisions' => 2,
        ],
        [
            'name' => 'Formatting to university style',
            'body' => 'Layout, headings, table of contents, figures, tables, citations and references set to your university\'s guidelines or APA, MLA, Chicago or IEEE.',
            'prices' => [Money::fromRupees(2500), Money::fromRupees(3500), Money::fromRupees(5000)],
            'turnaround' => '5 working days',
            'revisions' => 1,
        ],
        [
            'name' => 'Similarity check and guidance',
            'body' => 'A similarity report on your document and guidance on how to cite, quote and paraphrase properly to bring the score down honestly.',
            'prices' => [Money::fromRupees(500), Money::fromRupees(800), Money::fromRupees(1000)],
            'turnaround' => '2 working days',
            'revisions' => 0,
        ],
        [
            'name' => 'Research book editing and formatting',
            'body' => 'Editing, formatting and print-ready layout for a book based on your research.',
            'prices' => [null, null, null],
            'turnaround' => 'Quoted per book',
            'revisions' => null,
        ],
    ],

    // PLACEHOLDER add-on prices.
    'addons' => [
        ['name' => 'Spiral bound copy', 'price' => Money::fromRupees(150), 'unit' => 'per copy'],
        ['name' => 'Soft bound copy', 'price' => Money::fromRupees(250), 'unit' => 'per copy'],
        ['name' => 'Hard bound copy', 'price' => Money::fromRupees(600), 'unit' => 'per copy'],
        ['name' => 'Courier within the city', 'price' => Money::fromRupees(100), 'unit' => 'per order'],
        ['name' => 'Courier anywhere in India', 'price' => Money::fromRupees(250), 'unit' => 'per order'],
        ['name' => 'Express turnaround (half the time)', 'price' => Money::fromRupees(1000), 'unit' => 'per order'],
    ],

    'steps' => [
        ['title' => 'Tell us what you need', 'body' => 'Fill in the enquiry form or message us on WhatsApp with your level, service, deadline and page count.'],
        ['title' => 'Get a confirmed price', 'body' => 'We confirm the price from the list, or send a quote for larger jobs, and agree a delivery date.'],
        ['title' => 'Pay and share your files', 'body' => 'Pay by a secure UPI or card link, then send your draft and your university\'s guidelines.'],
        ['title' => 'Track it to delivery', 'body' => 'We keep you updated at each stage until your files, or your printed copies, reach you.'],
    ],

    // Add real expert profiles here: ['name' => '', 'subjects' => '', 'bio' => ''].
    // The section stays hidden while this list is empty.
    'experts' => [],

    // Add real quotes from students (with their permission): ['quote' => '', 'name' => '', 'detail' => 'MBA student'].
    // The section stays hidden while this list is empty.
    'testimonials' => [],

    'faqs' => [
        [
            'q' => 'Will you write my thesis or assignment for me?',
            'a' => 'No. We guide, edit, format and check the work you wrote. Submitting work written by someone else is misconduct at Indian universities, and under the UGC\'s 2018 anti-plagiarism rules it can cost you your degree. We will not put that at risk.',
        ],
        [
            'q' => 'Who works on my document?',
            'a' => 'An editor or subject expert with experience in your stream. You can talk to them through us at any stage.',
        ],
        [
            'q' => 'Is my work kept confidential?',
            'a' => 'Yes. Your files are seen only by you, your assigned expert and our admin, and are deleted 90 days after delivery unless you ask us to keep them.',
        ],
        [
            'q' => 'How does the similarity check help?',
            'a' => 'You get a report showing which passages match other sources, and guidance on how to quote, cite or rewrite them in your own words. We do not use tricks to hide matches.',
        ],
        [
            'q' => 'Can I get my thesis printed and delivered?',
            'a' => 'Yes. Choose spiral, soft or hard bound copies and courier as add-ons. Tell us your city when you enquire and we will confirm delivery.',
        ],
        [
            'q' => 'What if my deadline is close?',
            'a' => 'Add express turnaround and we aim to finish in half the usual time. Tell us your deadline when you enquire so we can confirm it is possible.',
        ],
        [
            'q' => 'I am under 18. Can I use this service?',
            'a' => 'Academic services are for students aged 18 or older. If you are in school, see our Tuition section.',
        ],
    ],

    'waitlist' => [
        'title' => 'Printing and delivery in your city',
        'body' => 'We are adding printing partners city by city. Join the waitlist and we will tell you when we deliver near you.',
        'interest' => 'Academic: printing and delivery in my city',
    ],

];
