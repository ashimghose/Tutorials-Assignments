<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Printing = 'printing';
    case Courier = 'courier';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Printing => 'Printing and binding',
            self::Courier => 'Courier',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $category) => [$category->value => $category->label()])->all();
    }
}
