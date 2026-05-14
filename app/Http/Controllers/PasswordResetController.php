<?php

namespace App\Http\Controllers;

use App\Notifications\SendPasswordResetLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function forgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $user->notify(new SendPasswordResetLink($token));
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'We have emailed your password reset link!');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function resetPasswordForm(string $token, Request $request)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been reset! Please sign in.');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
