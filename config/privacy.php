<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Consent policy version (DPDP Act)
    |--------------------------------------------------------------------------
    |
    | Stored with every consent. "draft-…" until a lawyer approves the privacy
    | policy and consent wording; change it when the wording changes.
    |
    */

    'policy_version' => env('CONSENT_POLICY_VERSION', 'draft-2026-10'),

];
