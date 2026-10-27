<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MKT-3: remembers where a visitor came from (utm_source, utm_medium,
 * utm_campaign, or the referring site) for the visit, so every lead,
 * waitlist sign-up and later order or enrolment can record it. A tagged
 * link always wins. Runs only with FEATURE_CMS: the static Phase 0 site
 * does this in the browser (resources/js/site.js).
 */
class CaptureUtm
{
    public const SESSION_KEY = 'utm';

    public const FIELDS = ['source', 'medium', 'campaign'];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('features.cms') && $request->isMethod('get') && $request->hasSession()) {
            $tagged = [];
            foreach (self::FIELDS as $field) {
                $value = $request->query("utm_{$field}");
                if (is_string($value) && $value !== '') {
                    $tagged[$field] = mb_substr($value, 0, 100);
                }
            }

            if ($tagged !== []) {
                $request->session()->put(self::SESSION_KEY, $tagged);
            } elseif (! $request->session()->has(self::SESSION_KEY)) {
                $request->session()->put(self::SESSION_KEY, ['source' => $this->referrerSource($request)]);
            }
        }

        return $next($request);
    }

    /**
     * @return array{source?: string, medium?: string, campaign?: string}
     */
    public static function current(Request $request): array
    {
        /** @var array{source?: string, medium?: string, campaign?: string} $utm */
        $utm = $request->hasSession() ? (array) $request->session()->get(self::SESSION_KEY, []) : [];

        return $utm;
    }

    private function referrerSource(Request $request): string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return is_string($host) && $host !== $request->getHost() ? mb_substr($host, 0, 100) : 'direct';
    }
}
