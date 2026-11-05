<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Mobile numbers, checked with Google's libphonenumber (the browser runs the
 * JavaScript port of the same library, resources/js/phone-field.js).
 *
 * A form sends a country (ISO 3166 code such as IN) and the number as typed.
 * We store the number in E.164 form, e.g. +919876543210.
 */
final class Phone
{
    public const string DEFAULT_COUNTRY = 'IN';

    /**
     * Shown first in the country list: India, then the other target markets.
     *
     * @var list<string>
     */
    public const array PREFERRED_COUNTRIES = ['IN', 'AE', 'BD'];

    /**
     * The number as a valid mobile, or null. A number typed with a leading +
     * keeps its own country code, whatever country was picked.
     */
    public static function parse(?string $number, ?string $country = null): ?PhoneNumber
    {
        $number = trim((string) $number);
        $country = self::country($country);
        if ($number === '' || mb_strlen($number) > 30) {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            $parsed = $util->parse($number, $country);
        } catch (NumberParseException) {
            return null;
        }

        if (! $util->isValidNumber($parsed)) {
            return null;
        }

        $type = $util->getNumberType($parsed);

        return in_array($type, [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE], true) ? $parsed : null;
    }

    public static function isValid(?string $number, ?string $country = null): bool
    {
        return self::parse($number, $country) !== null;
    }

    /**
     * E.164, e.g. +919876543210, or null when the number isn't a valid mobile.
     */
    public static function e164(?string $number, ?string $country = null): ?string
    {
        $parsed = self::parse($number, $country);

        return $parsed === null ? null : PhoneNumberUtil::getInstance()->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * A stored number for people to read: +91 98765 43210.
     */
    public static function display(?string $stored): string
    {
        $parsed = self::parseStored($stored);

        return $parsed === null ? (string) $stored : PhoneNumberUtil::getInstance()->format($parsed, PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * A WhatsApp chat link for a stored number.
     */
    public static function whatsappUrl(?string $stored): string
    {
        return 'https://wa.me/'.preg_replace('/\D/', '', self::e164($stored, self::DEFAULT_COUNTRY) ?? (string) $stored);
    }

    /**
     * A stored number split back into the two form fields.
     *
     * @return array{country: string, number: string}
     */
    public static function split(?string $stored): array
    {
        $parsed = self::parseStored($stored);
        if ($parsed === null) {
            return ['country' => self::DEFAULT_COUNTRY, 'number' => (string) $stored];
        }

        $util = PhoneNumberUtil::getInstance();

        return [
            'country' => $util->getRegionCodeForNumber($parsed) ?? self::DEFAULT_COUNTRY,
            'number' => self::withoutCountryCode($parsed),
        ];
    }

    /**
     * A sample mobile for the country, shown as the box's placeholder.
     */
    public static function example(string $country): string
    {
        $example = PhoneNumberUtil::getInstance()->getExampleNumberForType(self::country($country), PhoneNumberType::MOBILE);

        return $example === null ? '' : self::withoutCountryCode($example);
    }

    /**
     * The number as people write it after the country code: 98765 43210.
     */
    private static function withoutCountryCode(PhoneNumber $parsed): string
    {
        $international = PhoneNumberUtil::getInstance()->format($parsed, PhoneNumberFormat::INTERNATIONAL);

        return trim((string) preg_replace('/^\+\d+\s*/', '', $international));
    }

    /**
     * A known country code, upper-cased, or India.
     */
    public static function country(?string $country): string
    {
        $country = strtoupper(trim((string) $country));

        return in_array($country, self::countries(), true) ? $country : self::DEFAULT_COUNTRY;
    }

    /**
     * Every country libphonenumber knows, as ISO 3166 codes.
     *
     * @return list<string>
     */
    public static function countries(): array
    {
        static $countries = null;

        return $countries ??= array_values(PhoneNumberUtil::getInstance()->getSupportedRegions());
    }

    /**
     * The dialling code for a country, e.g. +91.
     */
    public static function dialCode(string $country): string
    {
        return '+'.PhoneNumberUtil::getInstance()->getCountryCodeForRegion(self::country($country));
    }

    /**
     * The flag emoji for a country code (regional indicator letters).
     */
    public static function flag(string $country): string
    {
        return implode('', array_map(
            fn (string $letter): string => mb_chr(0x1F1E6 + ord($letter) - ord('A')),
            str_split(self::country($country)),
        ));
    }

    /**
     * Older rows hold 10-digit Indian numbers without +91.
     */
    private static function parseStored(?string $stored): ?PhoneNumber
    {
        if (blank($stored)) {
            return null;
        }

        try {
            return PhoneNumberUtil::getInstance()->parse((string) $stored, self::DEFAULT_COUNTRY);
        } catch (NumberParseException) {
            return null;
        }
    }
}
