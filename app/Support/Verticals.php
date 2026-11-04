<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * VRT-1 to VRT-5: which sections are switched on, where each one lives
 * (a path on the main domain, or its own domain) and which brand its pages
 * show. Settings are in config/verticals.php.
 */
final class Verticals
{
    /** The sections, in menu order. Each one's landing page has the same route name. */
    public const KEYS = ['academic', 'tuition', 'music'];

    public static function exists(string $key): bool
    {
        return in_array($key, self::KEYS, true);
    }

    public static function enabled(string $key): bool
    {
        return self::exists($key) && (bool) config("verticals.{$key}.enabled");
    }

    /**
     * @return list<string>
     */
    public static function enabledKeys(): array
    {
        return array_values(array_filter(self::KEYS, self::enabled(...)));
    }

    /**
     * The section's own domain, or null when it lives on the main domain.
     */
    public static function domain(string $key): ?string
    {
        $domain = config("verticals.{$key}.domain");

        return filled($domain) ? strtolower((string) $domain) : null;
    }

    /**
     * The section whose own domain this is, if any.
     */
    public static function forHost(string $host): ?string
    {
        $host = strtolower($host);

        foreach (self::KEYS as $key) {
            if (self::domain($key) === $host) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The section a public request belongs to: the domain decides on a
     * section's own domain, otherwise the first part of the path does.
     */
    public static function forRequest(Request $request): ?string
    {
        return self::forHost($request->getHost())
            ?? (self::exists((string) $request->segment(1)) ? (string) $request->segment(1) : null);
    }

    /**
     * Link to a section's landing page (plus an optional #fragment), on its
     * own domain when it has one.
     */
    public static function url(string $key, string $fragment = ''): string
    {
        $fragment = $fragment === '' ? '' : '#'.ltrim($fragment, '#');
        $domain = self::domain($key);

        if ($domain !== null) {
            return self::scheme().'://'.$domain.'/'.$fragment;
        }

        // From a section's own domain, other sections live on the main domain.
        $onSectionDomain = app()->bound('request') && self::forHost(request()->getHost()) !== null;

        return ($onSectionDomain ? self::mainUrl($key) : route($key)).$fragment;
    }

    /**
     * The address on the main domain for a path, used to send visitors from
     * a section's own domain back to shared pages such as the log in.
     */
    public static function mainUrl(string $path = '/'): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @return array{name: string, logo: string, logo_alt: string}
     */
    public static function brand(?string $key): array
    {
        $name = $key !== null && self::exists($key) ? config("verticals.{$key}.brand.name") : null;
        $logo = $key !== null && self::exists($key) ? config("verticals.{$key}.brand.logo") : null;

        return [
            'name' => filled($name) ? (string) $name : (string) config('app.name'),
            'logo' => filled($logo) ? (string) $logo : (string) config('verticals.brand.logo'),
            'logo_alt' => filled($name) ? (string) $name : (string) config('verticals.brand.logo_alt'),
        ];
    }

    public static function scheme(): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME);

        return is_string($scheme) && $scheme !== '' ? $scheme : 'https';
    }
}
