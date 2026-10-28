<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rollout phase
    |--------------------------------------------------------------------------
    |
    | 0 = Listen (static site), 1 = Sell by hand, 2 = Platform core,
    | 3 = Own classroom. While the phase is below 2, public marketing pages
    | must stay exportable as static HTML.
    |
    */

    'phase' => (int) env('APP_PHASE', 0),

    /*
    |--------------------------------------------------------------------------
    | Phase 2+ modules
    |--------------------------------------------------------------------------
    |
    | Switch a module on only once its milestone is built and tested.
    |
    */

    'accounts' => filter_var(env('FEATURE_ACCOUNTS', false), FILTER_VALIDATE_BOOL),
    // M4: site content and leads in the database, edited at /admin; site
    // forms replace Google Forms.
    'cms' => filter_var(env('FEATURE_CMS', false), FILTER_VALIDATE_BOOL),
    // M5: tuition batches, timetables and enrolments (fees confirmed by the
    // admin until online checkout is switched on).
    'batches' => filter_var(env('FEATURE_BATCHES', false), FILTER_VALIDATE_BOOL),
    'checkout' => (bool) env('FEATURE_CHECKOUT', false),
    // M6: academic orders, price list and order statuses in the admin panel.
    'academic_orders' => filter_var(env('FEATURE_ACADEMIC_ORDERS', false), FILTER_VALIDATE_BOOL),
    // M7: in-app messages, dashboard notices and opt-in marketing emails.
    'chat' => filter_var(env('FEATURE_CHAT', false), FILTER_VALIDATE_BOOL),
    'live_classroom' => (bool) env('FEATURE_LIVE_CLASSROOM', false),
    'recordings' => (bool) env('FEATURE_RECORDINGS', false),

];
