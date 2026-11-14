<?php

/*
| Home page content (CMS-1, CMS-2). Edit the text here until M4's admin
| panel is live (FEATURE_CMS); after that the admin edits it at /admin.
*/

return [

    'hero' => [
        'title' => 'Guidance for your own assignments and research, school to PhD',
        'lead' => 'We help you make your own assignments, synopsis, thesis or dissertation clearer, correctly formatted and ready to submit. We also run live online tuition for classes 1 to 12, and guitar classes.',
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
            'body' => 'Guidance, editing, formatting and similarity checks for your assignments, synopsis, research paper, thesis or dissertation, plus printing, binding and delivery.',
            'points' => ['Assignments from school to PhD, any stream', 'Clear fixed prices, quotes for big jobs', 'Your work stays yours, and confidential'],
        ],
        // Shown once music is switched on (FEATURE_VERTICAL_MUSIC).
        'music' => [
            'eyebrow' => 'For children and adults',
            'title' => 'Guitar classes',
            'body' => 'Live online guitar classes in small groups, from your first chord to your first song, with a free trial class.',
            'points' => ['A live teacher who watches your hands', 'Children sign up through a parent', '₹1,000 a month'],
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
        'seo_title' => 'Assignment and thesis guidance, plus tuition and music',
        'seo_description' => 'Guidance, editing and formatting for your own assignments, theses and dissertations, school to PhD. Plus live tuition for classes 1 to 12 and guitar lessons.',
        'tuition_button' => 'Explore tuition',
        'academic_button' => 'Explore academic services',
        'music_button' => 'Explore guitar classes',
        'guides_title' => 'Free guides',
        'guides_lead' => 'Practical help for parents and students, whether or not you study with us.',
        'guides_link' => 'All guides',
        'guides_page_lead' => 'Practical help for parents of school children and for college students working on their research.',
        'help_title' => 'Not sure where to start?',
        'help_body' => 'Message us on WhatsApp and tell us what you need. We reply during office hours.',
        'help_button' => 'Message us on WhatsApp',
        'footer' => 'Academic guidance, editing and printing for your own work, school to PhD, plus live online tuition for classes 1 to 12 and music lessons.',
    ],

];
