<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO: www.<domain> served a full copy of the site, so search engines and
 * site checkers saw every page twice. Send it to the main domain for good.
 */
class RedirectWwwToMainDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $main = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($main) && $main !== '' && strcasecmp($request->getHost(), 'www.'.$main) === 0) {
            $root = rtrim((string) config('app.url'), '/');

            return redirect()->away($root.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
