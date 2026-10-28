<?php

namespace App\Filament\Resources\Users;

use App\Enums\AccountStatus;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * AUTH-6 admin actions on an account, shared by the list and view pages.
 * Each one is written to the audit log (ADM-6).
 */
class UserActions
{
    public static function deactivate(): Action
    {
        return Action::make('deactivate')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('They are logged out and cannot log in until you reactivate the account.')
            ->visible(fn (User $record): bool => $record->isActive() && $record->isNot(Auth::user()))
            ->action(function (User $record): void {
                $record->forceFill(['status' => AccountStatus::Deactivated])->save();
                AuditLog::record('user.deactivated', $record);
                Notification::make()->title('Account deactivated')->success()->send();
            });
    }

    public static function reactivate(): Action
    {
        return Action::make('reactivate')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => ! $record->isActive())
            ->action(function (User $record): void {
                $record->forceFill(['status' => AccountStatus::Active])->save();
                AuditLog::record('user.reactivated', $record);
                Notification::make()->title('Account reactivated')->success()->send();
            });
    }

    public static function unlock(): Action
    {
        return Action::make('unlock')
            ->icon(Heroicon::OutlinedLockOpen)
            ->visible(fn (User $record): bool => $record->isLocked())
            ->action(function (User $record): void {
                $record->forceFill(['locked_until' => null, 'failed_login_attempts' => 0])->save();
                AuditLog::record('user.unlocked', $record);
                Notification::make()->title('Account unlocked')->success()->send();
            });
    }
}
