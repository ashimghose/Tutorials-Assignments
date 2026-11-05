<?php

namespace App\Filament\Resources\Users;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

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

    /**
     * ADM-7: delete an account with nothing attached, such as a test sign-up.
     */
    public static function delete(): Action
    {
        return Action::make('delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete this account for good?')
            ->modalDescription('Only for accounts with no orders, classes or messages, such as a test sign-up. A parent\'s children and a child\'s details are deleted too. This cannot be undone.')
            ->visible(fn (User $record): bool => Gate::allows('delete', $record))
            ->action(function (User $record, Component $livewire): void {
                Gate::authorize('delete', $record);

                AuditLog::record('user.deleted', $record, ['role' => $record->role?->value]);
                // A school student's details go with their login; a parent's children go with theirs.
                $student = $record->role === Role::Student ? $record->student : null;
                $record->delete();
                $student?->delete();

                Notification::make()->title('Account deleted')->success()->send();
                $livewire->redirect(UserResource::getUrl('index'));
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
