<?php

namespace App\Support;

use App\Enums\LeadKind;

/**
 * The site's three forms (CMS-3, MKT-4) and how a submission is classified.
 */
final class LeadForms
{
    public const FORMS = ['tuition', 'academic', 'waitlist'];

    public static function offering(string $form, ?string $section = null): string
    {
        return match ($form) {
            'tuition', 'academic' => $form,
            default => $section === 'academic' ? 'academic' : 'tuition',
        };
    }

    public static function kind(string $form, ?string $interest): LeadKind
    {
        if ($form === 'waitlist') {
            return LeadKind::Waitlist;
        }

        return str_contains(strtolower((string) $interest), 'demo') ? LeadKind::Demo : LeadKind::Enquiry;
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
