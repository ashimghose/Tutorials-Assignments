<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate-limits Fortify's sign-up, forgot-password and reset-password forms,
 * which Fortify leaves unthrottled: 5 posts a minute per IP address.
 */
class ThrottleAccountForms
{
    private const ROUTES = ['register.store', 'password.email', 'password.update', 'invitation.accept'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! $request->routeIs(...self::ROUTES)) {
            return $next($request);
        }

        $key = 'account-forms|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, __('Too many attempts. Please wait a minute and try again.'));
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
