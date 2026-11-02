<?php

use App\Support\Money;

/*
| Music lessons section content (VRT-2, MUS). The admin edits it at /admin
| once FEATURE_VERTICAL_MUSIC is on. Prices are whole paise.
|
| PLACEHOLDER: every fee, instrument, style and schedule below is an
| example. Replace them with your real offer before switching music on.
*/

return [

    'hero' => [
        'title' => 'Live online music lessons: singing and instruments',
        'lead' => 'Learn from a real teacher, live, from home. Small groups or one-to-one lessons for children and adults, from your first note to exam level.',
    ],

    'highlights' => [
        ['title' => 'Group or one-to-one', 'body' => 'Join a small group of learners at your level, or book a one-to-one slot with the teacher.'],
        ['title' => 'Children and adults', 'body' => 'Parents sign up children and see every lesson and message. Adults (18+) sign up for themselves.'],
        ['title' => 'Clear sound', 'body' => 'Teachers set up their audio for music, and we tell you which call settings to change so your playing sounds right.'],
    ],

    // PLACEHOLDER: instruments and singing styles taught.
    'styles' => ['Hindustani vocal', 'Western vocal', 'Guitar', 'Keyboard', 'Tabla'],

    // PLACEHOLDER fees: monthly fee per learner.
    'plans' => [
        [
            'name' => 'Group lessons',
            'price' => Money::fromRupees(1500),
            'period' => 'per learner, per month',
            'subjects' => ['Hindustani vocal', 'Western vocal', 'Keyboard'],
            'features' => ['4 live lessons a month', '45 minutes each', 'Up to 6 learners at your level'],
        ],
        [
            'name' => 'One-to-one lessons',
            'price' => Money::fromRupees(3500),
            'period' => 'per learner, per month',
            'subjects' => ['Hindustani vocal', 'Western vocal', 'Guitar', 'Keyboard', 'Tabla'],
            'features' => ['4 live lessons a month', '45 minutes each', 'Lesson plan made for you'],
        ],
    ],

    'steps' => [
        ['title' => 'Tell us what you want to learn', 'body' => 'Fill in the short enquiry form or message us on WhatsApp with the instrument or style, your level and the learner\'s age.'],
        ['title' => 'Take a trial lesson', 'body' => 'Meet the teacher in one live lesson. You decide after that, with no obligation.'],
        ['title' => 'Pay the monthly fee', 'body' => 'We send you a secure payment link. Pay by UPI, card or net banking.'],
        ['title' => 'Lessons start', 'body' => 'You get the lesson link and timetable, and join from home at lesson time.'],
    ],

    // Shown from the teachers the admin marks "Show on website" (music).
    'teachers' => [],

    'testimonials' => [],

    'faqs' => [
        [
            'q' => 'How do online music lessons work?',
            'a' => 'Lessons run live on a video call. The teacher sings or plays, listens to you, and corrects you as you go. Use a laptop, tablet or phone with earphones.',
        ],
        [
            'q' => 'Do I need my own instrument?',
            'a' => 'Yes, for instrument lessons you need the instrument at home. For singing, a quiet room is enough. Ask us on WhatsApp if you are not sure what to buy.',
        ],
        [
            'q' => 'Can my child learn?',
            'a' => 'Yes. A parent signs the child up, and every message and notice goes to the parent. Teachers never chat privately with children.',
        ],
        [
            'q' => 'Is the trial lesson free?',
            'a' => 'Yes. You attend one full live lesson at no cost, and decide afterwards whether to join.',
        ],
        [
            'q' => 'How do I pay?',
            'a' => 'After the trial we send a secure payment link. You can pay by UPI, debit or credit card, or net banking. We never ask for card details on the phone or WhatsApp.',
        ],
    ],

    // MKT-3 price question on the enquiry form. PLACEHOLDER ranges.
    'price_ranges' => [
        'Up to ₹1,500 a month',
        '₹1,500 to ₹3,000 a month',
        'More than ₹3,000 a month',
    ],

    'waitlist' => [
        'title' => 'Your instrument or style not listed yet?',
        'body' => 'Join the waitlist and we will tell you when a teacher or group opens for it.',
        'interest' => 'Music: new instrument or style',
    ],

    // Headings, buttons and other short text around the sections above.
    'text' => [
        'seo_title' => 'Live online music lessons: singing and instruments',
        'seo_description' => 'Live online singing and instrument lessons for children and adults, in small groups or one-to-one, with a free trial lesson.',
        'eyebrow' => 'Music lessons for children and adults',
        'demo_button' => 'Book a free trial lesson',
        'question_button' => 'Ask a question',
        'styles_label' => 'We teach',
        'fees_eyebrow' => 'Fees',
        'fees_title' => 'Simple monthly fees',
        'fees_lead' => 'One fee per learner, per month. No admission fee, and the first lesson is a free trial.',
        'subjects_label' => 'Instruments and styles',
        'plan_button' => 'Book a free trial',
        'steps_eyebrow' => 'How it works',
        'steps_title' => 'From enquiry to first lesson',
        'people_eyebrow' => 'Teachers',
        'people_title' => 'Meet the teachers',
        'testimonials_eyebrow' => 'Learners say',
        'testimonials_title' => 'What learners and parents tell us',
        'faqs_eyebrow' => 'Questions',
        'faqs_title' => 'Questions learners ask',
        'cta_title' => 'Book a free trial lesson',
        'cta_body' => 'Tell us the instrument or style, your level and the learner\'s age, and we will set up a trial with the teacher.',
        'cta_button' => 'Book a free trial lesson',
        'waitlist_button' => 'Notify me',
        'guides_title' => 'Guides for music learners',
    ],

];
