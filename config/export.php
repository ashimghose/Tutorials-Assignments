<?php

/*
|--------------------------------------------------------------------------
| Static export (Phase 0–1)
|--------------------------------------------------------------------------
|
| `php artisan export` crawls the public pages from APP_URL and saves them
| as plain HTML in `dist/`, which GitHub Actions uploads to Cloudflare
| Pages (.github/workflows/deploy.yml). Links are absolute, so set APP_URL
| to the live domain before exporting. See docs/deploy.md.
|
*/

return [

    'crawl' => true,

    'use_streaming' => false,

    // Pages no visible link points to.
    'paths' => [
        'sitemap.xml',
        '404.html',
    ],

    'include_files' => [
        'public' => '',
    ],

    // Leave out the PHP entry point and local development leftovers.
    'exclude_file_patterns' => [
        '/\.php$/',
        '/mix-manifest\.json$/',
        '#public[\\\\/]hot$#',
        '#public[\\\\/]storage[\\\\/]#',
        '#public[\\\\/]fonts-manifest\.dev\.json$#',
        '#public[\\\\/]build[\\\\/]manifest\.json$#',
    ],

    'clean_before_export' => true,

    // Empty: export to the `dist` folder in the project root.
    'disk' => null,

    'before' => [],

    'after' => [],

];
