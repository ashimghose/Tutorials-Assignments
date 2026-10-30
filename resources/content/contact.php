<?php

/*
| Contact page (CMS-1). WhatsApp numbers, email and office hours come from
| .env (config/site.php); this is the text around them.
*/

return [

    'title' => 'Contact us',
    'lead' => 'The quickest way to reach us is WhatsApp. Pick the topic so we can route your message to the right person.',
    'description' => 'Contact us on WhatsApp or by email about tuition for classes 1 to 12 or academic services for college students.',

    'cards' => [
        'tuition' => [
            'title' => 'Tuition, classes 1 to 12',
            'body' => 'For parents asking about classes, fees or a free demo.',
            'whatsapp' => 'WhatsApp about tuition',
            'enquire' => 'Send an enquiry',
        ],
        'academic' => [
            'title' => 'Academic services',
            'body' => 'For students aged 18 or older asking about a service or a price.',
            'whatsapp' => 'WhatsApp about academic work',
            'enquire' => 'Send an enquiry',
        ],
    ],

    'office_hours_note' => 'Messages sent outside these hours are answered the next working day.',

];
