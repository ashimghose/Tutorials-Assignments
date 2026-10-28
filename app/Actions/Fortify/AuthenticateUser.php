<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Log in with an email address or, for school students, a username
 * (AUTH-2, AUTH-3). Locks the account for 15 minutes after 5 failed
 * passwords in a row and refuses deactivated accounts (AUTH-6).
 */
class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $login = (string) $request->input(Fortify::username());

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user === null || $user->password === null || $user->role === null) {
            return null;
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                Fortify::username() => __('Too many failed attempts. This account is locked until :time.', [
                    'time' => $user->locked_until->timezone(config('app.display_timezone'))->format('g:i a'),
                ]),
            ]);
        }

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            $this->recordFailure($user);

            return null;
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                Fortify::username() => __('This account has been deactivated. Please contact us.'),
            ]);
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        return $user;
    }

    private function recordFailure(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;

        if ($attempts >= User::MAX_FAILED_LOGINS) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => now()->addMinutes(User::LOCKOUT_MINUTES),
            ])->save();

            return;
        }

        $user->forceFill(['failed_login_attempts' => $attempts])->save();
    }
}
