<?php

use App\Support\Money;

/*
| Tuition section content (MKT-1). Edit the text here until M4 moves it into
| the admin panel. Prices are whole paise.
|
| PLACEHOLDER: every fee, subject, board and the class schedule below are
| examples. Replace them with your real offer before the site goes live.
*/

return [

    'hero' => [
        'title' => 'Live online tuition for classes 1 to 12',
        'lead' => 'Small batches of up to 15 students, taught live by a teacher who knows your child by name. Join from home on a laptop, tablet or phone.',
    ],

    'highlights' => [
        ['title' => 'Small live batches', 'body' => 'At most 15 students in a class, so every child can ask questions and be heard.'],
        ['title' => 'Notebook camera', 'body' => 'Teachers solve problems on paper or a whiteboard, the way your child will in the exam.'],
        ['title' => 'Parents stay in the loop', 'body' => 'Fees, notices and teacher messages come to you, the parent. Teachers never chat privately with your child.'],
    ],

    'boards' => ['CBSE', 'ICSE'],

    // PLACEHOLDER fees: monthly fee per subject, per child.
    'plans' => [
        [
            'name' => 'Classes 1 to 5',
            'price' => Money::fromRupees(1200),
            'period' => 'per subject, per month',
            'subjects' => ['Maths', 'English', 'EVS / Science', 'Hindi'],
            'features' => ['3 live classes a week', '55 minutes each', 'Monthly progress note for parents'],
        ],
        [
            'name' => 'Classes 6 to 8',
            'price' => Money::fromRupees(1500),
            'period' => 'per subject, per month',
            'subjects' => ['Maths', 'Science', 'English', 'Social Science'],
            'features' => ['3 live classes a week', '55 minutes each', 'Chapter tests every month'],
        ],
        [
            'name' => 'Classes 9 and 10',
            'price' => Money::fromRupees(2000),
            'period' => 'per subject, per month',
            'subjects' => ['Maths', 'Science', 'English', 'Social Science'],
            'features' => ['3 live classes a week', '55 minutes each', 'Board exam practice papers'],
        ],
        [
            'name' => 'Classes 11 and 12',
            'price' => Money::fromRupees(2500),
            'period' => 'per subject, per month',
            'subjects' => ['Physics', 'Chemistry', 'Maths', 'Biology', 'Accountancy', 'Economics'],
            'features' => ['3 live classes a week', '55 minutes each', 'Board and entrance exam practice'],
        ],
    ],

    'steps' => [
        ['title' => 'Tell us about your child', 'body' => 'Fill in the short enquiry form or message us on WhatsApp with the class, board and subjects.'],
        ['title' => 'Join a free demo class', 'body' => 'Your child attends one live class with the teacher. You decide after that, with no obligation.'],
        ['title' => 'Pay the monthly fee', 'body' => 'We send you a secure payment link. Pay by UPI, card or net banking.'],
        ['title' => 'Classes start', 'body' => 'You get the class link and timetable. Your child joins from home at class time.'],
    ],

    // Add real teacher profiles here: ['name' => '', 'subjects' => '', 'bio' => ''].
    // The section stays hidden while this list is empty.
    'teachers' => [],

    // Add real quotes from parents (with their permission): ['quote' => '', 'name' => '', 'detail' => 'Parent of a Class 8 student'].
    // The section stays hidden while this list is empty.
    'testimonials' => [],

    'faqs' => [
        [
            'q' => 'How do the online classes work?',
            'a' => 'Classes run live on Google Meet. Your child joins from a laptop, tablet or phone at the batch time, sees the teacher and the teacher\'s notebook, and can ask questions out loud or in the chat.',
        ],
        [
            'q' => 'How many students are in a batch?',
            'a' => 'At most 15. Small batches mean the teacher can check every child\'s work and answer every question.',
        ],
        [
            'q' => 'Which boards do you teach?',
            'a' => 'We currently teach CBSE and ICSE syllabi. Ask us on WhatsApp if your child follows a state board.',
        ],
        [
            'q' => 'Is the demo class really free?',
            'a' => 'Yes. Your child attends one full live class at no cost, and you decide afterwards whether to join.',
        ],
        [
            'q' => 'How do I pay?',
            'a' => 'After the demo we send a secure payment link. You can pay by UPI, debit or credit card, or net banking. We never ask for card details on the phone or WhatsApp.',
        ],
        [
            'q' => 'Is my child safe online?',
            'a' => 'We talk to parents, not children: enquiries, fees and messages go through you. Teachers do not chat privately with students, and we collect only your child\'s first name, class and board.',
        ],
        [
            'q' => 'What does my child need?',
            'a' => 'A laptop, tablet or phone with a camera, a stable internet connection, earphones, and a notebook.',
        ],
    ],

    'waitlist' => [
        'title' => 'Your class or subject not listed yet?',
        'body' => 'Join the waitlist and we will tell you when a batch opens for your child\'s class, board and subject.',
        'interest' => 'Tuition: new batch for my class or subject',
    ],

];
