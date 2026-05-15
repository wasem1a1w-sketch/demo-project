<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function showNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!URL::hasValidSignature($request)) {
            return redirect()->route('login')->with('error', 'Invalid or expired verification link.');
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('login')->with('success', 'Email verified successfully! You can now sign in.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent!');
    }
}
