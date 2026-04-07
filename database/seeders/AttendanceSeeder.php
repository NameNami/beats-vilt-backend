<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassSession;
use App\Models\CourseEnrollment;
use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed attendance records for a small subset of sessions
        $sessions = ClassSession::orderBy('start_time')->take(2)->get();
        if ($sessions->isEmpty()) {
            return; // prerequisites not met
        }

        foreach ($sessions as $session) {
            $studentIds = CourseEnrollment::where('course_id', $session->course_id)
                ->where('role', 'student')
                ->limit(12)
                ->pluck('user_id');

            $statuses = ['early', 'on-time', 'late'];

            foreach ($studentIds as $index => $userId) {
                $status = $statuses[$index % count($statuses)];
                $start  = Carbon::parse($session->start_time);

                // Determine check-in time based on status
                $checkIn = match ($status) {
                    'early'   => $start->copy()->subMinutes(5),
                    'on-time' => $start->copy(),
                    'late'    => $start->copy()->addMinutes(3),
                    default   => $start->copy(),
                };

                // Choose a valid check-in method
                $method = match ($status) {
                    'late'    => 'manual',
                    default   => $session->checkin_method, // BLE/dynamic_qr/static_qr
                };

                AttendanceRecord::updateOrCreate(
                    ['user_id' => $userId, 'session_id' => $session->id],
                    [
                        'check_in_time' => $checkIn,
                        'status'        => $status,
                        'checkin_method'=> $method,
                    ]
                );
            }
        }
    }
}
