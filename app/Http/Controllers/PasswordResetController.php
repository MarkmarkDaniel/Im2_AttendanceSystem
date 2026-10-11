<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * "Forgot password" form -- email address only.
     */
    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Emails a change-password link. Only student and instructor accounts
     * may use this; admins keep changing their password from the profile
     * page with their current password.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->isAdmin()) {
            return back()
                ->withInput()
                ->with('error', 'No student or instructor account was found for that email address.');
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput()
                ->with('error', 'We could not send a change password link. Please try again.');
        }

        return back()->with('success', 'Change password link has been sent to your registered email address.');
    }

    /**
     * A logged-in student or instructor asking to change their password.
     * The link always goes to the email already on the account, so nobody
     * can redirect it somewhere else.
     */
    public function sendLinkToSelf(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('profile.show')->with(
                'error',
                'Admin accounts change their password on this page using their current password.'
            );
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_THROTTLED) {
            return redirect()->route('profile.show')->with(
                'error',
                'A change password link was sent to you a moment ago. Please check your email inbox first.'
            );
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()->route('profile.show')->with(
                'error',
                'We could not send a change password link. Please try again.'
            );
        }

        return redirect()->route('profile.show')->with(
            'success',
            'Change password link has been sent to your registered email address.'
        );
    }

    /**
     * The "Change Password Form" the emailed button opens.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Applies the new password and invalidates the token.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->update(['password' => Hash::make($password)]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'This change password link is invalid or has expired. Please request a new one.');
        }

        // The user may have requested the link while still signed in. End that
        // session so the new password is the only way back in.
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')
            ->with('success', 'Your password has been changed. Please log in with your new password.');
    }
}
