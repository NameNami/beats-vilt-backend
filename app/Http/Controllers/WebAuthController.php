<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'lecturer') {
                return redirect()->route('lecturer.dashboard');
            }
        }
        return Inertia::render('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(
            ["email" => "required|email", "password" => "required|string"]
        );

        if (Auth::attempt($credentials))
        {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->role === 'lecturer')
            {
                return redirect()->intended('lecturer/dashboard');
            }
            if ($user->role === 'admin')
            {
                return redirect()->intended('admin/dashboard');
            }
            if ($user->role === 'student') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors(['email' => 'Students must use mobile app to login.']);
            }
        }
        return back()->withErrors(['email' => 'Invalid credentials.']);
    }
}
