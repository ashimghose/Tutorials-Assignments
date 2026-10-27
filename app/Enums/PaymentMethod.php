<?php

namespace App\Enums;

/**
 * How money reached the bank account (ADM-4).
 */
enum PaymentMethod: string
{
    case PaymentLink = 'payment_link';
    case Upi = 'upi';
    case BankTransfer = 'bank';
    case Cash = 'cash';
    case Gateway = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::PaymentLink => 'Razorpay Payment Link',
            self::Upi => 'UPI',
            self::BankTransfer => 'Bank transfer',
            self::Cash => 'Cash',
            self::Gateway => 'Online checkout',
        };
    }

    /**
     * The methods an admin records by hand.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect(self::cases())->reject(fn (self $method) => $method === self::Gateway)
            ->mapWithKeys(fn (self $method) => [$method->value => $method->label()])->all();
    }
}
