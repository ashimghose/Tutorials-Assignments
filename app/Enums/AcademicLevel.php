<?php

namespace App\Enums;

enum AcademicLevel: string
{
    case Bachelors = 'bachelors';
    case Masters = 'masters';
    case Phd = 'phd';

    public function label(): string
    {
        return match ($this) {
            self::Bachelors => 'Bachelors',
            self::Masters => 'Masters',
            self::Phd => 'PhD',
        };
    }

    /**
     * The services column holding this level's price.
     */
    public function priceColumn(): string
    {
        return 'price_'.$this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $level) => [$level->value => $level->label()])->all();
    }
}
