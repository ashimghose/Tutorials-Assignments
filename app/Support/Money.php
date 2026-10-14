<?php

namespace App\Support;

/**
 * Money is stored as whole paise. This turns paise into Indian display
 * format, for example 10000000 paise -> "₹1,00,000".
 */
final class Money
{
    public static function format(int $paise): string
    {
        $sign = $paise < 0 ? '-' : '';
        $paise = abs($paise);

        $rupees = intdiv($paise, 100);
        $remainder = $paise % 100;

        $formatted = '₹'.self::groupIndian((string) $rupees);

        if ($remainder !== 0) {
            $formatted .= '.'.str_pad((string) $remainder, 2, '0', STR_PAD_LEFT);
        }

        return $sign.$formatted;
    }

    public static function fromRupees(int $rupees): int
    {
        return $rupees * 100;
    }

    /**
     * Indian grouping: the last three digits, then groups of two.
     */
    private static function groupIndian(string $digits): string
    {
        if (strlen($digits) <= 3) {
            return $digits;
        }

        $lastThree = substr($digits, -3);
        $rest = substr($digits, 0, -3);
        $rest = (string) preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);

        return $rest.','.$lastThree;
    }
}
