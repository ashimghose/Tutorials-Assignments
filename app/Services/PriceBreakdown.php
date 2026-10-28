<?php

namespace App\Services;

use App\Models\ServiceAddon;

/**
 * An itemised price in paise, worked out on the server by Pricing.
 */
final readonly class PriceBreakdown
{
    /**
     * @param  list<array{addon: ServiceAddon, quantity: int, line_total: int}>  $addons
     */
    public function __construct(
        public int $base,
        public array $addons,
        public int $subtotal,
        public int $gstPercent,
        public int $gst,
        public int $total,
    ) {}
}
