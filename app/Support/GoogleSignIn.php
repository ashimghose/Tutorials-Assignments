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
    public const ACCOUNT_TYPES = ['parent', 'academic', 'music', 'teacher'];

    public const SESSION_KEY = 'google_signup';

    /**
     * Music learners can sign up only while the music section and batches are on (MUS-3).
     */
    public static function musicOpen(): bool
    {
        return Verticals::enabled('music') && (bool) config('features.batches');
    }

    public static function enabled(): bool
    {
        return config('features.accounts')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
