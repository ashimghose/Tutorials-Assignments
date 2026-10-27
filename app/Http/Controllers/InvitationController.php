<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AUTH-4: a teacher sets their own password from the invitation link. The
 * link is signed and expires; it works once, because it only applies to an
 * account that has no password yet.
 */
class InvitationController extends Controller
{
    use PasswordValidationRules;

    public function show(User $user): View
    {
        $this->ensurePending($user);

        return view('pages.auth.accept-invitation', ['user' => $user]);
    }

    public function accept(Request $request, User $user): RedirectResponse
    {
        $this->ensurePending($user);

        $request->validate(['password' => $this->passwordRules()]);

        // The link reached their inbox, which proves the address.
        $user->forceFill([
            'password' => $request->input('password'),
            'email_verified_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function ensurePending(User $user): void
    {
        abort_unless($user->role === Role::Teacher && $user->password === null && $user->isActive(), 410, __('This invitation has already been used or is no longer valid.'));
    }
}
