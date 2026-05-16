<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            $redirect = $user->can('admin.access') ? route('admin.dashboard') : route('home');

            UserActivityLog::record($user->id, 'user_login', "User logged in: {$user->email}");

            return Inertia::location($redirect);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('client');

        AdminNotification::notify('new_user_registered', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'message' => "New user registered: {$user->name}",
        ]);

        UserActivityLog::record($user->id, 'user_registered', "User registered: {$user->email}");

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        return Inertia::location(route('verification.notice'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location(route('home'));
    }
}
