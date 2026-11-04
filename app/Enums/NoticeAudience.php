<?php

namespace App\Enums;

enum NoticeAudience: string
{
    case Parents = 'parents';
    case Batch = 'batch';
    case AcademicStudents = 'academic';
    case MusicLearners = 'music';

    public function label(): string
    {
        return match ($this) {
            self::Parents => 'All parents',
            self::Batch => 'One batch (parents or music learners)',
            self::AcademicStudents => 'All academic students',
            self::MusicLearners => 'All music learners',
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
