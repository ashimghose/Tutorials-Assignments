<?php

namespace App\Enums;

/**
 * HIR-4: where a job application is in the hiring steps.
 */
enum JobApplicationStatus: string
{
    case New = 'new';
    case Shortlisted = 'shortlisted';
    case Hired = 'hired';
    case NotSelected = 'not_selected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Shortlisted => 'Shortlisted',
            self::Hired => 'Hired',
            self::NotSelected => 'Not selected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Shortlisted => 'info',
            self::Hired => 'success',
            self::NotSelected => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
