<?php

namespace App\Support;

/**
 * Builds the WhatsApp and Google Form links on the public site.
 */
final class SiteLinks
{
    /** The campaign fields resources/js/site.js fills in from the visitor's UTM tags. */
    public const UTM_FIELDS = ['source', 'medium', 'campaign'];

    /**
     * CHAT-6: click-to-chat with a different pre-filled message per section.
     */
    public static function whatsapp(string $section): string
    {
        $number = preg_replace('/\D/', '', (string) config('site.whatsapp.number'));
        $text = (string) (config("site.whatsapp.text.{$section}") ?? config('site.whatsapp.text.general'));

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }

    /**
     * MKT-3, MKT-4: a link to one of the Google Forms, pre-filled with the
     * page and what the visitor is interested in. The campaign fields are
     * added in the browser (see resources/js/site.js) so the page stays static.
     * Falls back to WhatsApp while the form URL isn't set.
     *
     * @return array{href: string, is_form: bool, utm_entries: array<string, string>}
     */
    public static function form(string $form, string $section, string $page, ?string $interest = null): array
    {
        $url = (string) config("site.forms.{$form}.url");

        if ($url === '') {
            return ['href' => self::whatsapp($section), 'is_form' => false, 'utm_entries' => []];
        }

        /** @var array<string, string|null> $entries */
        $entries = (array) config("site.forms.{$form}.entries", []);

        $prefill = [];
        foreach (['page' => $page, 'interest' => $interest] as $field => $value) {
            if (filled($entries[$field] ?? null) && filled($value)) {
                $prefill[(string) $entries[$field]] = $value;
            }
        }

        $utmEntries = [];
        foreach (self::UTM_FIELDS as $field) {
            if (filled($entries[$field] ?? null)) {
                $utmEntries[$field] = (string) $entries[$field];
            }
        }

        if ($prefill !== [] || $utmEntries !== []) {
            $prefill = ['usp' => 'pp_url'] + $prefill;
        }

        // Drop any ?usp=sf_link the copied share link carries.
        $url = explode('?', $url, 2)[0];
        $href = $prefill === [] ? $url : $url.'?'.http_build_query($prefill, '', '&', PHP_QUERY_RFC3986);

        return ['href' => $href, 'is_form' => true, 'utm_entries' => $utmEntries];
    }
}
