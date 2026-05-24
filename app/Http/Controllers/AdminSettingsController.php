<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        // Fetch all settings and format them as a simple key-value array for Vue
        $settings = Setting::pluck('value', 'key')->toArray();

        // Set defaults if the database is empty
        $defaultSettings = [
            'early_window_minutes' => $settings['early_window_minutes'] ?? '15',
            'late_cutoff_minutes' => $settings['late_cutoff_minutes'] ?? '30',
            'min_attendance_threshold' => $settings['min_attendance_threshold'] ?? '80',
            'qr_refresh_seconds' => $settings['qr_refresh_seconds'] ?? '15',
        ];

        return Inertia::render('Admin/SystemSettings', ['settings' => $defaultSettings]);
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
}
