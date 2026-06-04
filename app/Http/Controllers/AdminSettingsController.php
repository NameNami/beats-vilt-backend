<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
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
        $settings = SystemSetting::pluck('value', 'key')->toArray();

        // Process JSON for non_teaching_weeks if present
        $nonTeachingWeeks = [];
        if (isset($settings['non_teaching_weeks'])) {
            $nonTeachingWeeks = json_decode($settings['non_teaching_weeks'], true) ?? [];
        }

        // Set defaults if the database is missing keys
        $defaultSettings = [
            'app_name' => $settings['app_name'] ?? 'BEATS',
            'semester' => $settings['semester'] ?? '2025/2026-1',
            'semester_start_date' => $settings['semester_start_date'] ?? '',
            'semester_total_weeks' => $settings['semester_total_weeks'] ?? 14,
            'non_teaching_weeks' => $nonTeachingWeeks,
            'attendance_late_minutes' => $settings['attendance_late_minutes'] ?? 15,
            'ble_scan_timeout_seconds' => $settings['ble_scan_timeout_seconds'] ?? 30,
            'qr_rotation_seconds' => $settings['qr_rotation_seconds'] ?? 15,
            'xp_on_time' => $settings['xp_on_time'] ?? 50,
            'xp_late' => $settings['xp_late'] ?? 20,
            'points_on_time' => $settings['points_on_time'] ?? 10,
            'points_late' => $settings['points_late'] ?? 5,
            'min_attendance_threshold' => $settings['min_attendance_threshold'] ?? 80,
        ];

        return Inertia::render('Admin/SystemSettings', [
            'settings' => $defaultSettings,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name' => 'required|string',
            'semester' => 'required|string',
            'semester_start_date' => 'required|date',
            'semester_total_weeks' => 'required|numeric|min:1',
            'non_teaching_weeks' => 'nullable|array',
            'attendance_late_minutes' => 'required|numeric|min:0',
            'ble_scan_timeout_seconds' => 'required|numeric|min:5',
            'qr_rotation_seconds' => 'required|numeric|min:5',
            'xp_on_time' => 'required|numeric|min:0',
            'xp_late' => 'required|numeric|min:0',
            'points_on_time' => 'required|numeric|min:0',
            'points_late' => 'required|numeric|min:0',
            'min_attendance_threshold' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($data as $key => $value) {
            if ($key === 'non_teaching_weeks') {
                $value = json_encode($value ?: []);
            }
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
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
