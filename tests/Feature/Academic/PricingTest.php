<?php

use App\Enums\AcademicLevel;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Services\Pricing;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['academic.gst_percent' => 18]);
    $this->service = Service::factory()->create();
    $this->copy = ServiceAddon::factory()->create();
    $this->courier = ServiceAddon::factory()->courier()->create();
    $this->service->addons()->attach([$this->copy->id, $this->courier->id]);
});

test('PAY-1 total = base for the level + add-ons × quantity + GST, in paise', function () {
    $price = app(Pricing::class)->forService($this->service, AcademicLevel::Masters, [$this->copy->id => 3, $this->courier->id => 1]);

    // ₹8,000 + 3 × ₹600 + ₹250 = ₹10,050; GST 18% = ₹1,809.
    expect($price->base)->toBe(800000)
        ->and($price->subtotal)->toBe(1005000)
        ->and($price->gst)->toBe(180900)
        ->and($price->total)->toBe(1185900)
        ->and($price->addons)->toHaveCount(2);
});

test('GST is rounded to the nearest paisa', function () {
    $service = Service::factory()->create(['price_bachelors' => 333]);

    expect(app(Pricing::class)->forService($service, AcademicLevel::Bachelors, [])->gst)->toBe(60); // 59.94
});

test('per-order add-ons count once and copies are capped', function () {
    $price = app(Pricing::class)->forService($this->service, AcademicLevel::Phd, [$this->courier->id => 5, $this->copy->id => 500]);

    expect(collect($price->addons)->pluck('quantity', 'addon.id')->all())
        ->toBe([$this->courier->id => 1, $this->copy->id => Pricing::MAX_COPIES]);
});

test('an add-on the service does not offer, or one switched off, is refused', function () {
    $other = ServiceAddon::factory()->express()->create();
    expect(fn () => app(Pricing::class)->forService($this->service, AcademicLevel::Phd, [$other->id => 1]))->toThrow(ValidationException::class);

    $this->copy->update(['is_active' => false]);
    expect(fn () => app(Pricing::class)->forService($this->service, AcademicLevel::Phd, [$this->copy->id => 1]))->toThrow(ValidationException::class);
});

test('a level without a listed price must be quoted', function () {
    $service = Service::factory()->quoted()->create();

    expect(fn () => app(Pricing::class)->forService($service, AcademicLevel::Phd, []))->toThrow(ValidationException::class);
});

test('ACA-5 a quote gets the order\'s add-ons and GST on top', function () {
    $price = app(Pricing::class)->forQuote(2000000, 60000);

    expect($price->subtotal)->toBe(2060000)->and($price->gst)->toBe(370800)->and($price->total)->toBe(2430800);
});

test('with GST set to 0 there is no GST line', function () {
    config(['academic.gst_percent' => 0]);

    $price = app(Pricing::class)->forService($this->service, AcademicLevel::Bachelors, []);
    expect($price->gst)->toBe(0)->and($price->total)->toBe(500000);
});
