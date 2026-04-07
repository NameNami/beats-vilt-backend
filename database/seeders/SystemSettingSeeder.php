<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Core app settings
            ['key' => 'app_name',                   'value' => 'BEATS',                 'description' => 'Application name'],
            ['key' => 'semester',                   'value' => '2025/2026-1',           'description' => 'Active academic semester'],

            // Term dates and weeks
            ['key' => 'semester_start_date',        'value' => '2026-03-04',            'description' => 'Semester start date (YYYY-MM-DD)'],
            ['key' => 'semester_total_weeks',       'value' => '14',                    'description' => 'Number of teaching weeks in the semester'],
            ['key' => 'non_teaching_weeks',         'value' => '[]',                    'description' => 'JSON array of week-start dates (YYYY-MM-DD) that are non-teaching'],

            // Attendance configurations
            ['key' => 'attendance_early_minutes',   'value' => '10',                    'description' => 'Minutes before start time considered early'],
            ['key' => 'attendance_late_minutes',    'value' => '10',                    'description' => 'Minutes after start time considered late'],
            ['key' => 'ble_scan_timeout_seconds',   'value' => '30',                    'description' => 'BLE scan timeout in seconds'],
            ['key' => 'qr_rotation_seconds',        'value' => '30',                    'description' => 'Dynamic QR rotation interval in seconds'],

            // Gamification defaults
            ['key' => 'xp_on_time',                 'value' => '10',                    'description' => 'XP awarded for on-time check-in'],
            ['key' => 'xp_early',                   'value' => '15',                    'description' => 'XP awarded for early check-in'],
            ['key' => 'xp_late',                    'value' => '5',                     'description' => 'XP awarded for late check-in'],
            ['key' => 'points_on_time',             'value' => '5',                     'description' => 'Points awarded for on-time check-in'],
            ['key' => 'points_early',               'value' => '8',                     'description' => 'Points awarded for early check-in'],
            ['key' => 'points_late',                'value' => '2',                     'description' => 'Points awarded for late check-in'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'description' => $setting['description']]
            );
        }
    }
}
