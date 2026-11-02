<?php

/*
| Home page content (CMS-1, CMS-2). Edit the text here until M4's admin
| panel is live (FEATURE_CMS); after that the admin edits it at /admin.
*/

return [

    'hero' => [
        'title' => 'Learning support for school and beyond',
        'lead' => 'Live online classes for school children, and expert help for college students to make their own research work clearer, correctly formatted and ready to submit.',
    ],

    'offerings' => [
        'tuition' => [
            'eyebrow' => 'For parents',
            'title' => 'Tuition, classes 1 to 12',
            'body' => 'Live online classes in small batches of up to 15, following your child\'s school syllabus. Maths, Science, English and more, with a free demo class.',
            'points' => ['Live teacher, not recorded videos', 'Fees paid monthly, per subject', 'Parents get every message and notice'],
        ],
        'academic' => [
            'eyebrow' => 'For college students, 18+',
            'title' => 'Academic services',
            'body' => 'Guidance, editing, formatting and similarity checks for your synopsis, research paper, thesis or dissertation, plus printing, binding and delivery.',
            'points' => ['Bachelors, Masters and PhD, any stream', 'Clear fixed prices, quotes for big jobs', 'Your work stays yours, and confidential'],
        ],
    ],

    // A one-line notice across the top of every public page. Hidden while
    // the text is empty.
    'banner' => [
        'text' => '',
        'link' => '',
    ],

    // Headings, buttons and other short text around the sections above.
    // 'footer' and the 'guides_' lines also appear on other pages.
    'text' => [
        'seo_title' => 'Online tuition for classes 1 to 12, and thesis and dissertation help',
        'seo_description' => 'Live online tuition for classes 1 to 12 in small batches, and guidance, editing, formatting and printing for Bachelors, Masters and PhD students\' research work.',
        'tuition_button' => 'Explore tuition',
        'academic_button' => 'Explore academic services',
        'guides_title' => 'Free guides',
        'guides_lead' => 'Practical help for parents and students, whether or not you study with us.',
        'guides_link' => 'All guides',
        'guides_page_lead' => 'Practical help for parents of school children and for college students working on their research.',
        'help_title' => 'Not sure where to start?',
        'help_body' => 'Message us on WhatsApp and tell us what you need. We reply during office hours.',
        'help_button' => 'Message us on WhatsApp',
        'footer' => 'Live online tuition for classes 1 to 12, and academic guidance, editing and printing for college students.',
    ],

];
