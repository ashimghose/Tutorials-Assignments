<?php

namespace App\Support;

/**
 * AUTH-7: "Continue with Google" shows only once accounts are on and the
 * Google OAuth client is set in .env.
 */
class GoogleSignIn
{
    /**
     * Account types someone can pick when they join with Google.
     */
    public const ACCOUNT_TYPES = ['parent', 'academic', 'music'];

    public const SESSION_KEY = 'google_signup';

    public static function enabled(): bool
    {
        return config('features.accounts')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
