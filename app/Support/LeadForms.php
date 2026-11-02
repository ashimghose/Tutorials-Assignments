<?php

namespace App\Support;

use App\Enums\LeadKind;

/**
 * The site's forms (CMS-3, MKT-4): one per section plus the waitlist, and
 * how a submission is classified.
 */
final class LeadForms
{
    public const FORMS = ['tuition', 'academic', 'music', 'waitlist'];

    public static function offering(string $form, ?string $section = null): string
    {
        if (Verticals::exists($form)) {
            return $form;
        }

        return in_array($section, ['academic', 'music'], true) && Verticals::enabled($section) ? $section : 'tuition';
    }

    public static function kind(string $form, ?string $interest): LeadKind
    {
        if ($form === 'waitlist') {
            return LeadKind::Waitlist;
        }

        // A music trial lesson is the music section's free demo.
        return preg_match('/\b(demo|trial)\b/i', (string) $interest) === 1 ? LeadKind::Demo : LeadKind::Enquiry;
    }

    /**
     * @return list<string>
     */
    public static function priceRanges(string $offering): array
    {
        /** @var list<string> $ranges */
        $ranges = SiteContent::section($offering)['price_ranges'] ?? [];

        return $ranges;
    }
}
