<?php

/*
|--------------------------------------------------------------------------
| Verticals (VRT-1 to VRT-5)
|--------------------------------------------------------------------------
|
| The business's three sections. Each can be switched off, and each can be
| served on its own domain later: set VERTICAL_DOMAIN_<NAME> (for example
| "music.example.in") and its pages move there, with permanent redirects
| from the old addresses on the main domain. Empty = the main domain.
|
| The brand (name and logo) shown on a section's pages can differ from the
| main brand; empty values use APP_NAME and the main logo.
|
*/

$vertical = fn (string $name, bool $enabled) => [
    'enabled' => filter_var(env("FEATURE_VERTICAL_{$name}", $enabled), FILTER_VALIDATE_BOOL),
    'domain' => env("VERTICAL_DOMAIN_{$name}") ?: null,
    'brand' => [
        'name' => env("VERTICAL_BRAND_{$name}") ?: null,
        'logo' => env("VERTICAL_LOGO_{$name}") ?: null,
    ],
];

return [

    'tuition' => $vertical('TUITION', true),
    'academic' => $vertical('ACADEMIC', true),
    // MUS: the music page stays hidden until its copy and prices are ready.
    'music' => $vertical('MUSIC', false),

    // The main brand, used on shared pages and by any section without its own.
    'brand' => [
        'logo' => env('BRAND_LOGO', '/images/brand/indian-assignments-logo.svg'),
        'logo_alt' => env('BRAND_LOGO_ALT', 'Indian Assignments'),
    ],

];
