<?php

namespace App\Enums;

/**
 * ADM-1: an expert is paid a fixed amount per completed order or a
 * percentage of the order's service price (without GST and add-ons).
 */
enum OrderRateType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed amount per order',
            self::Percent => '% of the service price',
        };
    }
}
