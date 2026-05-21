<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Models\User;
use App\Models\UserActivityLog;
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

    public function sendResetLink(PasswordResetRequest $request)
    {
        $status = Password::broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $user->notify(new SendPasswordResetLink($token));
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                UserActivityLog::record($user->id, 'password_reset_requested', "Password reset requested for: {$request->email}");
            }

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

    public function updatePassword(PasswordUpdateRequest $request)
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                UserActivityLog::record($user->id, 'password_reset_completed', "Password reset completed for: {$request->email}");
            }

            return redirect()->route('login')->with('success', 'Your password has been reset! Please sign in.');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
