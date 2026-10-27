<?php

return [

    /*
    | GST added on top of the academic price list and quotes (PAY-1). Set by
    | the owner's accountant; 0 shows no GST line.
    */
    'gst_percent' => (int) env('ACADEMIC_GST_PERCENT', 18),

    /*
    | Order files (ACA-4): a private disk ("local" on a laptop, an R2 disk in
    | production), the largest upload in kilobytes, and how long files are
    | kept after an order is finished or cancelled.
    */
    'files_disk' => env('ORDER_FILES_DISK', 'local'),
    'max_upload_kb' => 25 * 1024,
    'max_files' => 5,
    'retention_days' => (int) env('ORDER_FILE_RETENTION_DAYS', 180),
    'mimes' => [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],

    // ACA-5: how long a quote stays open unless the admin picks a date.
    'quote_valid_days' => 7,

];
