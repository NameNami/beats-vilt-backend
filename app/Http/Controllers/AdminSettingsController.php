<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AdminSettingsController extends Controller
{
    public function index()
    {
        // Fetch all settings and format them as a simple key-value array for Vue
        $settings = Setting::pluck('value', 'key')->toArray();

        // Set defaults if the database is empty
        $defaultSettings = [
            'early_window_minutes' => $settings['early_window_minutes'] ?? '60',
            'late_cutoff_minutes' => $settings['late_cutoff_minutes'] ?? '30',
            'min_attendance_threshold' => $settings['min_attendance_threshold'] ?? '80',
            'qr_refresh_seconds' => $settings['qr_refresh_seconds'] ?? '15',
        ];

        return Inertia::render('Admin/SystemSettings', [
            'settings' => $defaultSettings,
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'early_window_minutes' => 'required|numeric',
            'late_cutoff_minutes' => 'required|numeric',
            'min_attendance_threshold' => 'required|numeric',
            'qr_refresh_seconds' => 'required|numeric',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Global system settings updated successfully.');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        $user = \App\Models\User::find(auth()->id());

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile-photos', 'public');

        $user->profile_photo_path = $path;
        $user->save();

        return back()->with('success', 'Photo updated successfully.');
    }

    public function deletePhoto()
    {
        $user = auth()->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('success', 'Photo deleted successfully.');
    }
}
