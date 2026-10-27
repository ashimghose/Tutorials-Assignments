<?php

namespace App\Enums;

/**
 * AUTH-5: one login table for everyone; the role decides what a user may
 * reach. Never mass-assigned: set it with forceFill() where the account is made.
 */
enum Role: string
{
    case Parent = 'parent';
    case Student = 'student';
    case AcademicStudent = 'academic_student';
    case Teacher = 'teacher';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Parent => 'Parent',
            self::Student => 'School student',
            self::AcademicStudent => 'Academic student',
            self::Teacher => 'Teacher / expert',
            self::Admin => 'Admin',
        };
    }

    /**
     * The page each role lands on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Parent => 'parent.children',
            self::Student => 'student.home',
            self::AcademicStudent => 'academic.home',
            self::Teacher => 'teacher.home',
            self::Admin => 'filament.admin.pages.dashboard',
        };
    }
}
