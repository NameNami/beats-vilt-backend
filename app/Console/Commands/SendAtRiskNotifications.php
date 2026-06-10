<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendAtRiskNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-at-risk-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly notifications to students with attendance below the threshold.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting at-risk attendance check...');

        $threshold = (float) SystemSetting::get('min_attendance_threshold', 80);
        $students = User::where('role', 'student')->with('courseEnrollments.course')->get();
        $now = now();
        $notifications = [];

        foreach ($students as $student) {
            foreach ($student->courseEnrollments as $enrollment) {
                $course = $enrollment->course;
                if (!$course) continue;

                // Get all completed sessions the student should have attended for this course
                $totalSessionsIds = ClassSession::where('course_id', $course->id)
                    ->where('is_completed', true)
                    ->where(function($query) use ($enrollment) {
                        $query->whereNull('lab_id')
                              ->orWhere('lab_id', $enrollment->lab_id);
                    })
                    ->pluck('id');

                $totalCount = $totalSessionsIds->count();
                if ($totalCount === 0) continue;

                // Count successful attendances
                $attendedCount = AttendanceRecord::whereIn('session_id', $totalSessionsIds)
                    ->where('user_id', $student->id)
                    ->whereIn('status', ['on-time', 'late', 'present', 'leave'])
                    ->count();

                $percentage = ($attendedCount / $totalCount) * 100;

                if ($percentage < $threshold) {
                    $formattedPercent = round($percentage, 1);
                    $notifications[] = [
                        'user_id' => $student->id,
                        'title' => 'Attendance Warning: ' . $course->code,
                        'body' => "Your current attendance for {$course->code} is {$formattedPercent}%, which is below the required {$threshold}%. Please ensure you attend future classes to avoid academic penalties.",
                        'type' => 'alert',
                        'is_read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($notifications)) {
            // Chunk to avoid issues with large inserts
            foreach (array_chunk($notifications, 500) as $chunk) {
                Notification::insert($chunk);
            }
            $this->info('Sent ' . count($notifications) . ' at-risk notifications.');
        } else {
            $this->info('No at-risk students found this week.');
        }

        return Command::SUCCESS;
    }
}
