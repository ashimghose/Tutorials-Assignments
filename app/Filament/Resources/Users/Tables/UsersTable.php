<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Filament\Resources\Users\UserActions;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')
                    ->label('Email or username')
                    ->state(fn (User $record): ?string => $record->email ?? $record->username)
                    ->searchable(['email', 'username']),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (?Role $state): string => $state?->label() ?? '—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AccountStatus $state): string => ucfirst($state->value))
                    ->color(fn (AccountStatus $state): string => $state === AccountStatus::Active ? 'success' : 'danger'),
                IconColumn::make('locked')
                    ->state(fn (User $record): bool => $record->isLocked())
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('')
                    ->trueColor('warning'),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime('j M Y, g:i a', timezone: config('app.display_timezone'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y', timezone: config('app.display_timezone'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(Role::cases())->mapWithKeys(fn (Role $role) => [$role->value => $role->label()])),
                SelectFilter::make('status')
                    ->options([AccountStatus::Active->value => 'Active', AccountStatus::Deactivated->value => 'Deactivated']),
            ])
            ->recordActions([
                ViewAction::make(),
                UserActions::unlock(),
                UserActions::deactivate(),
                UserActions::reactivate(),
            ]);
    }
}
