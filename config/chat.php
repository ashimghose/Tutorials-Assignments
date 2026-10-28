<?php

return [

    /*
    | CHAT-5: office hours in India time. Outside them the chat says when we
    | will reply. Days are ISO numbers (Monday = 1).
    */
    'office_hours' => [
        'days' => array_map('intval', explode(',', (string) env('CHAT_OFFICE_DAYS', '1,2,3,4,5,6'))),
        'opens' => env('CHAT_OFFICE_OPENS', '10:00'),
        'closes' => env('CHAT_OFFICE_CLOSES', '19:00'),
    ],

    // NOT-1: email a member about a message they haven't read after this many minutes.
    'unread_email_after_minutes' => 15,

    // CHAT-3: attachments (checked from the file's content), up to 10 MB.
    'attachment_max_kb' => 10 * 1024,
    'attachment_mimes' => [
        'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],

    // Messages a person may send per minute.
    'per_minute' => 10,

];
