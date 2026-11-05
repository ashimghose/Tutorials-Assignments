<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\Role;
use App\Models\User;
use App\Support\GoogleSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * AUTH-7: "Continue with Google" on the login and sign-up pages.
 *
 * - A Google account already linked, or whose verified email matches an
 *   account, logs straight in (with the two-factor step if it is on).
 * - A new person answers the sign-up questions (account type, mobile, age,
 *   consent) on a short "finish your account" page before an account is
 *   made, so the parent, 18+ and music-guardian rules still apply.
 * - School students log in with their username only (AUTH-3).
 * - Someone with no password confirms "it's me" (before security settings)
 *   by signing in to the same Google account again.
 */
class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirect
    {
        abort_unless(GoogleSignIn::enabled(), 404);

        $as = $request->query('as');
        $request->session()->put(GoogleSignIn::SESSION_KEY.'_as', in_array($as, GoogleSignIn::ACCOUNT_TYPES, true) ? $as : null);

        return Socialite::driver('google')->redirect();
    }

    public function confirm(Request $request): SymfonyRedirect
    {
        abort_unless(GoogleSignIn::enabled() && $request->user()->google_id !== null, 404);

        $request->session()->put(GoogleSignIn::SESSION_KEY.'_confirm', true);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(GoogleSignIn::enabled(), 404);

        if ($request->user() !== null) {
            return $this->confirmed($request);
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            // Cancelled on Google's screen, an expired state, or Google unreachable.
            return $this->fail(__('Google sign-in did not finish. Please try again.'));
        }

        $email = Str::lower(trim((string) $google->getEmail()));

        if ($email === '' || ! filter_var($google->user['email_verified'] ?? false, FILTER_VALIDATE_BOOL)) {
            return $this->fail(__('Your Google account has no verified email address.'));
        }

        $user = User::query()->where('google_id', $google->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if ($user !== null) {
            return $this->logIn($request, $user, (string) $google->getId());
        }

        if (! Features::enabled(Features::registration())) {
            return $this->fail(__('We could not find an account for :email.', ['email' => $email]));
        }

        $request->session()->put(GoogleSignIn::SESSION_KEY, [
            'id' => (string) $google->getId(),
            'name' => Str::limit(trim((string) $google->getName()), 255, ''),
            'email' => $email,
        ]);

        return redirect()->route('google.finish', array_filter(['as' => $request->session()->pull(GoogleSignIn::SESSION_KEY.'_as')]));
    }

    /**
     * The sign-up questions Google can't answer for us.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $google = $request->session()->get(GoogleSignIn::SESSION_KEY);

        if ($google === null) {
            return redirect()->route('register');
        }

        return view('pages::auth.register', [
            'accountType' => in_array($request->query('as'), ['academic', 'music'], true) ? $request->query('as') : 'parent',
            'google' => $google,
        ]);
    }

    public function store(Request $request, CreateNewUser $creator): RedirectResponse
    {
        $google = $request->session()->get(GoogleSignIn::SESSION_KEY);

        if ($google === null) {
            return redirect()->route('register');
        }

        $user = $creator->createFromGoogle(
            $request->except(['email', 'password', 'password_confirmation', 'google_id']),
            $google['id'],
            $google['email'],
        );

        $request->session()->forget(GoogleSignIn::SESSION_KEY);
        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function logIn(Request $request, User $user, string $googleId): RedirectResponse
    {
        if ($user->role === null || $user->role === Role::Student) {
            return $this->fail(__('This account can\'t use Google sign-in. Please log in with your username and password.'));
        }

        if ($user->google_id !== null && $user->google_id !== $googleId) {
            return $this->fail(__('This account is linked to a different Google account.'));
        }

        if ($user->isLocked()) {
            return $this->fail(__('Too many failed attempts. This account is locked until :time.', [
                'time' => $user->locked_until->timezone(config('app.display_timezone'))->format('g:i a'),
            ]));
        }

        if (! $user->isActive()) {
            return $this->fail(__('This account has been deactivated. Please contact us.'));
        }

        // Google has verified the address, which also proves an invited teacher's email.
        $user->forceFill([
            'google_id' => $googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'failed_login_attempts' => 0,
            'last_login_at' => now(),
        ])->save();

        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function confirmed(Request $request): RedirectResponse
    {
        try {
            $google = $request->session()->pull(GoogleSignIn::SESSION_KEY.'_confirm') ? Socialite::driver('google')->user() : null;
        } catch (Throwable) {
            $google = null;
        }

        if ($google === null || (string) $google->getId() !== $request->user()->google_id) {
            return redirect()->route('password.confirm')->withErrors(['password' => __('Please confirm with the Google account you signed up with.')]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
