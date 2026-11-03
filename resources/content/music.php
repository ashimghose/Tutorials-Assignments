<?php

use App\Support\Money;

/*
| Music section content (VRT-2, MUS). Starts with guitar group classes; more
| instruments can be added later. The admin edits it at /admin once
| FEATURE_VERTICAL_MUSIC is on. Prices are whole paise.
|
| The fee is the owner's (₹1,000 a month for online group classes). Class
| length, classes a month and group size are not set yet, so the copy below
| doesn't promise them.
*/

return [

    'hero' => [
        'title' => 'Learn guitar online, live in a small group',
        'lead' => 'Live group guitar classes with a real teacher, from your first chord to your first full song. Join from home with your own guitar, for ₹1,000 a month.',
    ],

    // Highlighted badges under the headline (MKT-1); none by default.
    'keywords' => [],

    'highlights' => [
        ['title' => 'A live teacher, not a video course', 'body' => 'The teacher watches your hands, listens to you play and corrects your fingers and strumming as you go.'],
        ['title' => 'Learn songs you know', 'body' => 'Chords and strumming patterns are taught through Bollywood, pop and folk songs, so practice never feels like homework.'],
        ['title' => 'Children and adults', 'body' => 'Parents sign up children and get every message and notice. Adults (18+) sign up for themselves.'],
    ],

    // What is taught. Add other instruments here when they start.
    'styles' => ['Acoustic guitar', 'Chords and strumming', 'Songs and rhythm'],

    'plans' => [
        [
            'name' => 'Guitar group classes',
            'price' => Money::fromRupees(1000),
            'period' => 'per learner, per month',
            'subjects' => ['Acoustic guitar'],
            'features' => ['Live online group classes', 'Beginners welcome', 'Practice tips after every class'],
        ],
    ],

    // The four chords drawn on the page (strings low E to high e: x = not
    // played, 0 = open, a number = the fret to press).
    'chords' => [
        ['name' => 'Em', 'frets' => '022000'],
        ['name' => 'C', 'frets' => 'x32010'],
        ['name' => 'G', 'frets' => '320003'],
        ['name' => 'D', 'frets' => 'xx0232'],
    ],

    'steps' => [
        ['title' => 'Tell us about the learner', 'body' => 'Fill in the short enquiry form or message us on WhatsApp with the learner\'s age and whether they have played before.'],
        ['title' => 'Take a free trial class', 'body' => 'Join one live group class with the teacher. You decide after that, with no obligation.'],
        ['title' => 'Pay ₹1,000 for the month', 'body' => 'We send you a secure payment link. Pay by UPI, card or net banking.'],
        ['title' => 'Play along every week', 'body' => 'You get the class link and timetable, and join from home with your guitar at class time.'],
    ],

    // Short videos shown on the page. Paste a YouTube link in the admin; the
    // section stays hidden while the list is empty.
    'videos' => [],

    // Shown from the teachers the admin marks "Show on website" (music).
    'teachers' => [],

    'testimonials' => [],

    'faqs' => [
        [
            'q' => 'How do online guitar classes work?',
            'a' => 'Classes run live on a video call. The teacher plays and explains, then watches and listens while you play, and corrects you. Use a laptop, tablet or phone placed so the teacher can see both your hands.',
        ],
        [
            'q' => 'Do I need my own guitar?',
            'a' => 'Yes. An acoustic guitar is best for starting. You don\'t need an expensive one, and we are happy to advise on WhatsApp before you buy.',
        ],
        [
            'q' => 'I have never played. Can I join?',
            'a' => 'Yes. Beginners are welcome, and the first chords are taught from the very start: how to hold the guitar, how to tune it and how to press the strings.',
        ],
        [
            'q' => 'Can my child learn?',
            'a' => 'Yes, children who can hold a guitar comfortably can join; a smaller guitar helps younger children. A parent signs the child up, and every message and notice goes to the parent. Teachers never chat privately with children.',
        ],
        [
            'q' => 'How much does it cost?',
            'a' => '₹1,000 a month per learner for the online group classes. There is no admission fee, and the first class is a free trial.',
        ],
        [
            'q' => 'How do I pay?',
            'a' => 'After the trial we send a secure payment link. You can pay by UPI, debit or credit card, or net banking. We never ask for card details on the phone or WhatsApp.',
        ],
    ],

    // MKT-3 price question on the enquiry form, to test what people will pay.
    'price_ranges' => [
        'Up to ₹1,000 a month',
        '₹1,000 to ₹2,000 a month',
        'More than ₹2,000 a month',
    ],

    'waitlist' => [
        'title' => 'Want another instrument or singing?',
        'body' => 'Join the waitlist and we will tell you when classes start for keyboard, singing or another instrument.',
        'interest' => 'Music: another instrument or singing',
    ],

    // Headings, buttons and other short text around the sections above.
    'text' => [
        'seo_title' => 'Online guitar classes in small groups, ₹1,000 a month',
        'seo_description' => 'Live online guitar group classes for children and adults with a real teacher. Chords, strumming and songs for beginners, ₹1,000 a month, free trial class.',
        'eyebrow' => 'Guitar classes for children and adults',
        'demo_button' => 'Book a free trial class',
        'question_button' => 'Ask a question',
        'styles_label' => 'You learn',
        'chords_eyebrow' => 'Your first month',
        'chords_title' => 'Four chords that play hundreds of songs',
        'chords_lead' => 'Em, C, G and D are the first chords most learners master. With them you can already play along to many popular songs.',
        'chords_note' => 'How to read a chord box: the six lines are the strings, low E on the left. A dot is where a finger presses, O is a string played open and X is a string you don\'t play.',
        'fees_eyebrow' => 'Fees',
        'fees_title' => 'One simple monthly fee',
        'fees_lead' => 'One fee per learner, per month. No admission fee, and the first class is a free trial.',
        'subjects_label' => 'Instrument',
        'plan_button' => 'Book a free trial',
        'steps_eyebrow' => 'How it works',
        'steps_title' => 'From enquiry to your first song',
        'videos_eyebrow' => 'Watch',
        'videos_title' => 'See a class in action',
        'people_eyebrow' => 'Teachers',
        'people_title' => 'Meet the teachers',
        'testimonials_eyebrow' => 'Learners say',
        'testimonials_title' => 'What learners and parents tell us',
        'faqs_eyebrow' => 'Questions',
        'faqs_title' => 'Questions learners ask',
        'cta_title' => 'Book a free trial class',
        'cta_body' => 'Tell us the learner\'s age and whether they have played before, and we will book a trial class with the teacher.',
        'cta_button' => 'Book a free trial class',
        'waitlist_button' => 'Notify me',
        'guides_title' => 'Guides for guitar learners',
    ],

];
