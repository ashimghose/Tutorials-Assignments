<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('role')->formatStateUsing(fn (?Role $state): string => $state?->label() ?? '—'),
                        TextEntry::make('email')->placeholder('—'),
                        TextEntry::make('username')->placeholder('—'),
                        TextEntry::make('status')->badge()->formatStateUsing(fn (AccountStatus $state): string => ucfirst($state->value)),
                        TextEntry::make('email_verified_at')->label('Email verified')->dateTime('j M Y, g:i a', $tz)->placeholder('Not yet'),
                        TextEntry::make('last_login_at')->label('Last login')->dateTime('j M Y, g:i a', $tz)->placeholder('Never'),
                        TextEntry::make('locked_until')->label('Locked until')->dateTime('j M Y, g:i a', $tz)->placeholder('Not locked'),
                    ]),
                Section::make('Parent')
                    ->visible(fn (User $record): bool => $record->role === Role::Parent)
                    ->schema([
                        TextEntry::make('parentProfile.mobile')->label('Mobile'),
                        RepeatableEntry::make('parentProfile.students')
                            ->label('Children')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('first_name'),
                                TextEntry::make('class_level')->label('Class'),
                                TextEntry::make('board'),
                            ]),
                    ]),
                Section::make('Academic student')
                    ->visible(fn (User $record): bool => $record->role === Role::AcademicStudent)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('academicStudent.mobile')->label('Mobile'),
                        TextEntry::make('academicStudent.adult_declared_at')->label('Declared 18+')->dateTime('j M Y, g:i a', $tz),
                        TextEntry::make('academicStudent.university')->label('University')->placeholder('—'),
                        TextEntry::make('academicStudent.course')->label('Course')->placeholder('—'),
                    ]),
                Section::make('School student')
                    ->visible(fn (User $record): bool => $record->role === Role::Student)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('student.class_level')->label('Class'),
                        TextEntry::make('student.board')->label('Board'),
                        TextEntry::make('student.parent.user.name')->label('Parent'),
                    ]),
                Section::make('Teacher')
                    ->visible(fn (User $record): bool => $record->role === Role::Teacher)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('teacher.offering')->label('Works in')->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('teacher.subjects')->label('Subjects')->badge()->placeholder('—'),
                        TextEntry::make('password')
                            ->label('Invitation')
                            ->state(fn (User $record): string => $record->password === null ? 'Waiting for them to set a password' : 'Accepted'),
                    ]),
            ]);
    }
}
