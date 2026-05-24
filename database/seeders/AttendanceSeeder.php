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
        // Seed attendance records for completed sessions
        $sessions = ClassSession::where('is_completed', true)->get();
        if ($sessions->isEmpty()) {
            return; // prerequisites not met
        }

        foreach ($sessions as $session) {
            $enrollments = CourseEnrollment::where('course_id', $session->course_id)
                ->where('role', 'student')
                ->where(function($q) use ($session) {
                    $q->whereNull('lab_id')->orWhere('lab_id', $session->lab_id);
                })
                ->get();

            foreach ($enrollments as $enrollment) {
                $userId = $enrollment->user_id;
                
                // Create some deterministic "at-risk" students based on their ID
                // Students with IDs divisible by 3 will have poor attendance (~60%)
                // Others will have good attendance (~90%)
                $isAtRiskCandidate = ($userId % 3 === 0);
                $random = rand(1, 100);
                
                $status = 'absent';
                $checkIn = null;
                $method = 'manual';

                if ($isAtRiskCandidate) {
                    if ($random <= 60) {
                        $status = $this->getRandomPresentStatus();
                    } elseif ($random <= 70) {
                        $status = 'leave';
                    }
                } else {
                    if ($random <= 90) {
                        $status = $this->getRandomPresentStatus();
                    } elseif ($random <= 95) {
                        $status = 'leave';
                    }
                }

                if (in_array($status, ['early', 'on-time', 'late', 'present'])) {
                    $start = Carbon::parse($session->start_time);
                    $checkIn = match ($status) {
                        'early'   => $start->copy()->subMinutes(rand(5, 15)),
                        'on-time' => $start->copy()->addMinutes(rand(0, 5)),
                        'late'    => $start->copy()->addMinutes(rand(11, 25)),
                        'present' => $start->copy()->addMinutes(rand(0, 10)),
                    };
                    $method = ($status === 'late') ? 'manual' : 'ble';
                } else {
                    $checkIn = Carbon::parse($session->start_time)->addMinutes(rand(0, 120));
                }

                AttendanceRecord::updateOrCreate(
                    ['user_id' => $userId, 'session_id' => $session->id],
                    [
                        'check_in_time' => $checkIn,
                        'status'        => $status,
                        'checkin_method'=> $method,
                        'created_at'    => $checkIn,
                        'updated_at'    => $checkIn,
                    ]
                );
            }
        }
    }

    private function getRandomPresentStatus()
    {
        $statuses = ['early', 'on-time', 'late', 'present'];
        $weights = [20, 50, 20, 10]; // Probabilities
        
        $r = rand(1, 100);
        $current = 0;
        foreach ($weights as $index => $weight) {
            $current += $weight;
            if ($r <= $current) {
                return $statuses[$index];
            }
        }
        return 'on-time';
    }
}
