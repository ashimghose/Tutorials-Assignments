<?php

namespace App\Enums;

/**
 * What a site form was for (CMS-3, MKT-4).
 */
enum LeadKind: string
{
    case Enquiry = 'enquiry';
    case Demo = 'demo';
    case Waitlist = 'waitlist';

    public function label(): string
    {
        return match ($this) {
            self::Enquiry => 'Enquiry',
            self::Demo => 'Demo booking',
            self::Waitlist => 'Waitlist',
        };
    }
}
