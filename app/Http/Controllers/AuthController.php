<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Check if user is active
            if (!$user->isActive()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is inactive. Please contact administrator.',
                ]);
            }

            // Redirect based on role
            if ($user->isAdmin()) {
                return redirect()->intended('/admin/dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
            } else {
                return redirect()->intended('/user/dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
            }
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'You have been logged out successfully.');
    }
}