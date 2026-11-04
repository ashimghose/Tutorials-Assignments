<?php

namespace App\Enums;

use App\Support\Verticals;

/**
 * The business's verticals (VRT-1): school tuition, academic services and
 * music lessons. "Both" is kept for site content (articles, testimonials)
 * that belongs to every section.
 */
enum Offering: string
{
    case Academic = 'academic';
    case Tuition = 'tuition';
    case Music = 'music';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Tuition => 'Tuition',
            self::Academic => 'Academic services',
            self::Music => 'Music lessons',
            self::Both => 'All sections',
        };
    }

    /**
     * Filament badge colour, the same in every admin table.
     */
    public function color(): string
    {
        return match ($this) {
            self::Tuition => 'warning',
            self::Academic => 'primary',
            self::Music => 'info',
            self::Both => 'gray',
        };
    }

    /**
     * The verticals that are switched on (VRT-1), in menu order.
     *
     * @return list<self>
     */
    public static function sections(): array
    {
        return array_values(array_filter(
            [self::Academic, self::Tuition, self::Music],
            fn (self $offering) => Verticals::enabled($offering->value),
        ));
    }

    /**
     * @return array<string, string> value => label of the switched-on verticals
     */
    public static function sectionOptions(): array
    {
        return collect(self::sections())->mapWithKeys(fn (self $offering) => [$offering->value => $offering->label()])->all();
    }

    /**
     * @return list<string>
     */
    public static function sectionValues(): array
    {
        return array_map(fn (self $offering) => $offering->value, self::sections());
    }
}
