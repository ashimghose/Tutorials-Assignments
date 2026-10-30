<?php

/*
|--------------------------------------------------------------------------
| Public marketing site (Phase 0–1)
|--------------------------------------------------------------------------
|
| Contact details, Google Form links and analytics for the static site.
| The values are read when the pages are exported, so set them in .env
| before running `php artisan export`.
|
*/

return [

    'contact' => [
        'email' => env('CONTACT_EMAIL'),
        'office_hours' => env('OFFICE_HOURS', 'Monday to Saturday, 10 am to 7 pm IST'),
        'city' => env('BUSINESS_CITY'),
    ],

    // CHAT-6: a different pre-filled message per section. Tuition and
    // academic chats can go to their own numbers; a section without one, and
    // the general pages, use WHATSAPP_NUMBER.
    'whatsapp' => [
        'number' => env('WHATSAPP_NUMBER'),
        'numbers' => [
            'tuition' => env('WHATSAPP_NUMBER_TUITION'),
            'academic' => env('WHATSAPP_NUMBER_ACADEMIC'),
        ],
        'text' => [
            'general' => env('WHATSAPP_TEXT_GENERAL', 'Hi, I have a question'),
            'tuition' => env('WHATSAPP_TEXT_TUITION', "Hi, I'm asking about tuition"),
            'academic' => env('WHATSAPP_TEXT_ACADEMIC', "Hi, I'm asking about academic services"),
        ],
    ],

    // MKT-3, MKT-4: Google Forms for enquiries and waitlists. The entry ids
    // come from each form's "Get pre-filled link" and let the site fill in
    // where the visitor came from. Each form has its own entry ids; leave
    // one blank to skip that question. See docs/google-forms.md.
    'forms' => [
        'tuition' => [
            'url' => env('FORM_URL_TUITION'),
            'entries' => [
                'source' => env('FORM_TUITION_ENTRY_SOURCE'),
                'medium' => env('FORM_TUITION_ENTRY_MEDIUM'),
                'campaign' => env('FORM_TUITION_ENTRY_CAMPAIGN'),
                'page' => env('FORM_TUITION_ENTRY_PAGE'),
                'interest' => env('FORM_TUITION_ENTRY_INTEREST'),
            ],
        ],
        'academic' => [
            'url' => env('FORM_URL_ACADEMIC'),
            'entries' => [
                'source' => env('FORM_ACADEMIC_ENTRY_SOURCE'),
                'medium' => env('FORM_ACADEMIC_ENTRY_MEDIUM'),
                'campaign' => env('FORM_ACADEMIC_ENTRY_CAMPAIGN'),
                'page' => env('FORM_ACADEMIC_ENTRY_PAGE'),
                'interest' => env('FORM_ACADEMIC_ENTRY_INTEREST'),
            ],
        ],
        'waitlist' => [
            'url' => env('FORM_URL_WAITLIST'),
            'entries' => [
                'source' => env('FORM_WAITLIST_ENTRY_SOURCE'),
                'medium' => env('FORM_WAITLIST_ENTRY_MEDIUM'),
                'campaign' => env('FORM_WAITLIST_ENTRY_CAMPAIGN'),
                'page' => env('FORM_WAITLIST_ENTRY_PAGE'),
                'interest' => env('FORM_WAITLIST_ENTRY_INTEREST'),
            ],
        ],
    ],

    // Cloudflare Web Analytics (cookie-less), public pages only.
    'analytics_token' => env('CF_ANALYTICS_TOKEN'),

    // Google Analytics 4 measurement id (G-XXXXXXXXXX). Public pages only, for
    // visitors who aren't logged in, and only after they accept analytics cookies.
    'google_analytics_id' => env('GA_MEASUREMENT_ID'),

];
