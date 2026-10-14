<?php

use App\Support\Money;

test('formats paise in Indian rupees with Indian digit grouping', function (int $paise, string $expected) {
    expect(Money::format($paise))->toBe($expected);
})->with([
    [0, '₹0'],
    [50, '₹0.50'],
    [120000, '₹1,200'],
    [10000000, '₹1,00,000'],
    [1234567891, '₹1,23,45,678.91'],
    [-250000, '-₹2,500'],
]);

test('converts whole rupees to paise', function () {
    expect(Money::fromRupees(1500))->toBe(150000);
});
