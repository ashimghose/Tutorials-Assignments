<?php

namespace App\Http\Middleware;

use App\Support\Verticals;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VRT-1, VRT-3: hides a switched-off section, and sends visitors to the
 * right domain once a section has its own.
 *
 * - On the main domain, /music (and anything below it) moves permanently
 *   (301) to the music domain when one is set, keeping the path after the
 *   section name and the query string, so UTM tags survive.
 * - On a section's own domain, "/" is that section's landing page, the old
 *   /music path moves to "/", and another section's path moves to that
 *   section. Shared public pages (guides, about, enquiry forms…) work on
 *   every domain; logins, dashboards and the admin stay on the main domain,
 *   where the session cookie lives.
 */
class ResolveVertical
{
    /** First path segments that work on a section's own domain too. */
    public const SHARED = ['', 'resources', 'about', 'contact', 'privacy', 'terms', 'refund-policy', 'enquire', 'c', 'sitemap.xml', '404.html', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        $first = (string) $request->segment(1);
        $hostKey = Verticals::forHost($request->getHost());

        if ($hostKey !== null) {
            abort_unless(Verticals::enabled($hostKey), 404);

            if (Verticals::exists($first)) {
                abort_unless(Verticals::enabled($first), 404);

                return redirect()->away($this->target($request, $first, $first === $hostKey ? '' : null), 301);
            }

            if (! in_array($first, self::SHARED, true)) {
                return redirect()->away(Verticals::mainUrl($this->pathWithQuery($request)));
            }

            return $next($request);
        }

        if (Verticals::exists($first)) {
            abort_unless(Verticals::enabled($first), 404);

            if (Verticals::domain($first) !== null) {
                return redirect()->away($this->target($request, $first), 301);
            }
        }

        return $next($request);
    }

    /**
     * Where a section's path should go: its own domain, or its path on the
     * main domain. $rest overrides the path after the section name.
     */
    private function target(Request $request, string $key, ?string $rest = null): string
    {
        $segments = $request->segments();
        $rest ??= implode('/', array_slice($segments, 1));
        $query = $request->getQueryString();
        $domain = Verticals::domain($key);

        $base = $domain !== null
            ? Verticals::scheme().'://'.$domain
            : rtrim(Verticals::mainUrl(), '/').'/'.$key;

        $url = $rest === '' ? ($domain !== null ? $base.'/' : $base) : rtrim($base, '/').'/'.$rest;

        return $query ? $url.'?'.$query : $url;
    }

    private function pathWithQuery(Request $request): string
    {
        $query = $request->getQueryString();

        return ltrim($request->path(), '/').($query ? '?'.$query : '');
    }
}
