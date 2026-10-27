<?php

namespace App\Enums;

enum MarketingAudience: string
{
    case Parents = 'parents';
    case AcademicStudents = 'academic';
    case TuitionLeads = 'tuition_leads';
    case AcademicLeads = 'academic_leads';

    public function label(): string
    {
        return match ($this) {
            self::Parents => 'Parents who opted in',
            self::AcademicStudents => 'Academic students who opted in',
            self::TuitionLeads => 'Tuition enquiries who opted in',
            self::AcademicLeads => 'Academic enquiries who opted in',
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
