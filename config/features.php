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
    'checkout' => (bool) env('FEATURE_CHECKOUT', false),
    'academic_orders' => (bool) env('FEATURE_ACADEMIC_ORDERS', false),
    'chat' => (bool) env('FEATURE_CHAT', false),
    'live_classroom' => (bool) env('FEATURE_LIVE_CLASSROOM', false),
    'recordings' => (bool) env('FEATURE_RECORDINGS', false),

];
