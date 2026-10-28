<?php

namespace App\Enums;

enum NoticeAudience: string
{
    case Parents = 'parents';
    case Batch = 'batch';
    case AcademicStudents = 'academic';

    public function label(): string
    {
        return match ($this) {
            self::Parents => 'All parents',
            self::Batch => 'Parents of one batch',
            self::AcademicStudents => 'All academic students',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $audience) => [$audience->value => $audience->label()])->all();
    }
}
