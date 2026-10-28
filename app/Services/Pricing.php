<?php

namespace App\Services;

use App\Enums\AcademicLevel;
use App\Enums\AddonUnit;
use App\Models\Service;
use App\Models\ServiceAddon;
use Illuminate\Validation\ValidationException;

/**
 * PAY-1, ACA-4: order total = base price + add-ons × quantity + GST, or an
 * admin quote + GST. Prices always come from the database, never from the
 * browser. Whole paise; GST is rounded to the nearest paisa.
 */
class Pricing
{
    public const MAX_COPIES = 20;

    /**
     * @param  array<int|string, int|string|null>  $quantities  add-on id => quantity
     */
    public function forService(Service $service, AcademicLevel $level, array $quantities): PriceBreakdown
    {
        $base = $service->priceFor($level);
        if ($base === null) {
            throw ValidationException::withMessages(['service' => __('This service is quoted for your level. Please ask for a quote.')]);
        }

        return $this->withGst($base, $this->addonLines($service, $quantities));
    }

    /**
     * The chosen add-ons the service allows, priced from the list. Per-order
     * add-ons count once; copies are capped.
     *
     * @param  array<int|string, int|string|null>  $quantities  add-on id => quantity
     * @return list<array{addon: ServiceAddon, quantity: int, line_total: int}>
     */
    public function addonLines(Service $service, array $quantities): array
    {
        $addons = [];
        $allowed = $service->addons()->where('is_active', true)->get()->keyBy('id');

        foreach ($quantities as $id => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity <= 0) {
                continue;
            }

            /** @var ServiceAddon|null $addon */
            $addon = $allowed->get((int) $id);
            if ($addon === null) {
                throw ValidationException::withMessages(['addons' => __('One of the add-ons is not available for this service.')]);
            }

            $quantity = $addon->unit === AddonUnit::PerOrder ? 1 : min($quantity, self::MAX_COPIES);
            $addons[] = ['addon' => $addon, 'quantity' => $quantity, 'line_total' => $addon->price * $quantity];
        }

        return $addons;
    }

    /**
     * ACA-5: the admin's quote for the job, plus any add-ons already on the
     * order, with GST on top.
     */
    public function forQuote(int $amount, int $addonsTotal = 0): PriceBreakdown
    {
        return $this->withGst($amount, [], $addonsTotal);
    }

    /**
     * @param  list<array{addon: ServiceAddon, quantity: int, line_total: int}>  $addons
     */
    private function withGst(int $base, array $addons, int $otherAddons = 0): PriceBreakdown
    {
        $subtotal = $base + array_sum(array_column($addons, 'line_total')) + $otherAddons;
        $percent = max(0, (int) config('academic.gst_percent'));
        $gst = intdiv($subtotal * $percent + 50, 100);

        return new PriceBreakdown($base, $addons, $subtotal, $percent, $gst, $subtotal + $gst);
    }
}
