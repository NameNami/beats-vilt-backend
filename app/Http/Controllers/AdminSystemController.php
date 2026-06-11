<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\CourseEnrollment;
use App\Models\GamificationProfile;
use App\Models\LeaveApplication;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminSystemController extends Controller
{
    public function health()
    {
        // Gather basic database health metrics
        $dbConnection = env('DB_CONNECTION', 'sqlite');
        $dbName = env('DB_DATABASE', database_path('database.sqlite'));

        $dbSize = 0;
        if ($dbConnection === 'sqlite' && file_exists($dbName)) {
            $dbSize = filesize($dbName) / 1024 / 1024; // MB
        } elseif ($dbConnection === 'mysql') {
            $result = DB::select("SELECT table_schema AS 'db', SUM(data_length + index_length) / 1024 / 1024 AS 'size' FROM information_schema.TABLES WHERE table_schema = ? GROUP BY table_schema", [$dbName]);
            if (! empty($result)) {
                $dbSize = $result[0]->size;
            }
        }

        // Basic environment info
        $health = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => $dbConnection,
            'db_size_mb' => round($dbSize, 2),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
        ];

        return Inertia::render('Admin/SystemHealth', [
            'health' => $health,
        ]);
    }

    public function downloadBackup()
    {
        $dbConnection = env('DB_CONNECTION');

        if ($dbConnection === 'sqlite') {
            $dbPath = env('DB_DATABASE', database_path('database.sqlite'));
            if (! file_exists($dbPath)) {
                return back()->withErrors(['message' => 'SQLite database file not found.']);
            }

            return response()->download($dbPath, 'backup_'.date('Y_m_d_His').'.sqlite');
        }

        if ($dbConnection === 'mysql') {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');

            $filename = "backup_{$database}_".date('Y_m_d_His').'.sql';
            $path = storage_path("app/private/{$filename}");

            // Note: This requires mysqldump to be in the system PATH
            $passwordStr = empty($password) ? '' : "-p\"{$password}\"";
            $command = "mysqldump -h {$host} -P {$port} -u {$username} {$passwordStr} {$database} > \"{$path}\"";

            // Use exec to run mysqldump
            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || ! file_exists($path) || filesize($path) === 0) {
                if (file_exists($path)) {
                    unlink($path);
                }

                return back()->withErrors(['message' => 'Failed to generate MySQL backup. Ensure mysqldump is installed and accessible.']);
            }

            return response()->download($path)->deleteFileAfterSend(true);
        }

        return back()->withErrors(['message' => 'Backup is not currently supported for this database driver.']);
    }

    public function rolloverSemester(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:CONFIRM',
            'new_semester' => 'required|string',
            'new_start_date' => 'required|date',
        ]);

        $currentSemester = SystemSetting::get('semester', 'Unknown-Semester');

        DB::beginTransaction();

        try {
            // 1. Move Class Sessions
            $sessions = ClassSession::all();
            $archivedSessions = [];
            foreach ($sessions as $session) {
                $archivedSessions[] = [
                    'semester_tag' => $currentSemester,
                    'original_id' => $session->id,
                    'course_id' => $session->course_id,
                    'lecturer_id' => $session->lecturer_id,
                    'lab_id' => $session->lab_id,
                    'room_id' => $session->room_id,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'type' => $session->type,
                    'is_completed' => $session->is_completed,
                    'is_cancelled' => $session->is_cancelled,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at,
                ];
            }
            foreach (array_chunk($archivedSessions, 500) as $chunk) {
                DB::table('archived_class_sessions')->insert($chunk);
            }

            // 2. Move Course Enrollments (Only Students)
            $enrollments = CourseEnrollment::where('role', 'student')->get();
            $archivedEnrollments = [];
            foreach ($enrollments as $enrollment) {
                $archivedEnrollments[] = [
                    'semester_tag' => $currentSemester,
                    'original_id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'course_id' => $enrollment->course_id,
                    'lab_id' => $enrollment->lab_id,
                    'role' => $enrollment->role,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'created_at' => $enrollment->created_at,
                    'updated_at' => $enrollment->updated_at,
                ];
            }
            foreach (array_chunk($archivedEnrollments, 500) as $chunk) {
                DB::table('archived_course_enrollments')->insert($chunk);
            }

            // 3. Move Attendance Records
            $attendances = AttendanceRecord::all();
            $archivedAttendances = [];
            foreach ($attendances as $attendance) {
                $archivedAttendances[] = [
                    'semester_tag' => $currentSemester,
                    'original_id' => $attendance->id,
                    'session_id' => $attendance->session_id,
                    'user_id' => $attendance->user_id,
                    'status' => $attendance->status,
                    'scanned_at' => $attendance->scanned_at,
                    'is_manual' => $attendance->is_manual,
                    'created_at' => $attendance->created_at,
                    'updated_at' => $attendance->updated_at,
                ];
            }
            foreach (array_chunk($archivedAttendances, 500) as $chunk) {
                DB::table('archived_attendance_records')->insert($chunk);
            }

            // 4. Delete the active data (Order matters due to foreign keys)
            AttendanceRecord::query()->delete();
            LeaveApplication::query()->delete();
            // Delete student enrollments, keep lecturer assignments
            CourseEnrollment::where('role', 'student')->delete();
            ClassSession::query()->delete();

            // 5. Update the System Settings
            SystemSetting::updateOrCreate(
                ['key' => 'semester'],
                ['value' => $request->new_semester]
            );
            SystemSetting::updateOrCreate(
                ['key' => 'semester_start_date'],
                ['value' => $request->new_start_date]
            );

            // 6. Reset Gamification Streaks
            GamificationProfile::query()->update(['current_streak' => 0]);

            DB::commit();

            return back()->with('success', 'Semester successfully archived and rolled over!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['message' => 'Rollover failed: '.$e->getMessage()]);
        }
    }
}
