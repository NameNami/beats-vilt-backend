<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ClassSession;
use App\Models\CourseEnrollment;
use App\Models\AttendanceRecord;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebAttendanceController extends Controller
{
    /**
     * TODO: make live attendance
     * TODO: search bar for student (Handled by passing all data to Vue for quick filtering)
     */
    public function show($id)
    {
        $session = ClassSession::with(['course', 'lab', 'room'])->findOrFail($id);

        // Get all students supposed to be in this class
        $query = CourseEnrollment::where('course_id', $session->course_id)->where('role', 'student');

        // If it's a lab session, only get students assigned to this specific lab
        if ($session->lab_id) {
            $query->where('lab_id', $session->lab_id);
        }

        $enrolledStudents = $query->with('user')->get()->pluck('user');

        // Get the actual check-in records for this session
        $attendanceRecords = AttendanceRecord::where('session_id', $id)->get()->keyBy('user_id');

        // Combine the data so Vue has a clean list of who is present and who is absent
        $studentsList = $enrolledStudents->map(function ($student) use ($attendanceRecords) {
            $record = $attendanceRecords->get($student->id);
            return [
                'id' => $student->id,
                'name' => $student->name,
                'student_id' => $student->student_id,
                'status' => $record ? $record->status : 'Absent',
                'checkin_method' => $record ? $record->checkin_method : null,
                'check_in_time' => $record ? $record->check_in_time : null,
            ];
        });

        return Inertia::render('Lecturer/LiveAttendance', [
            'session' => $session,
            'students' => $studentsList
        ]);
    }

    /**
     * TODO: qr generator
     * TODO: make sure to modify the is display for class session
     */
    public function toggleSession(Request $request, $id)
    {
        $session = ClassSession::findOrFail($id);

        if ($session->is_display) {
            // Close the session: hide QR and expire any active tokens
            $session->update([
                'is_display' => false,
            ]);

            $session->qrTokens()->where('expires_at', '>', now())->update([
                'expires_at' => now()
            ]);

            return back()->with('success', 'Attendance session closed.');
        } else {
            // Open the session and generate the secure QR/BLE Token
            $session->update([
                'is_display' => true,
            ]);

            $token = hash('sha256', $session->id . Str::random(40) . now());

            $session->qrTokens()->create([
                'token' => $token,
                'expires_at' => now()->addMinutes(15) // Dynamic QR expires in 15 mins
            ]);

            return back()->with('success', 'Session opened! QR Token generated.');
        }
    }

    /**
     * TODO: button for manual attendance
     */
    public function markManual(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:on-time,late,absent' // Using lowercase to match DB/Service standards
        ]);

        if ($request->status === 'absent') {
            // If marking absent, just delete their record if they had one or mark as absent
            AttendanceRecord::updateOrCreate(
                ['session_id' => $id, 'user_id' => $request->user_id],
                [
                    'check_in_time' => now(),
                    'status' => 'absent',
                    'checkin_method' => 'manual'
                ]
            );
        } else {
            // Update or create the record
            AttendanceRecord::updateOrCreate(
                ['session_id' => $id, 'user_id' => $request->user_id],
                [
                    'check_in_time' => now(),
                    'status' => $request->status,
                    'checkin_method' => 'manual'
                ]
            );
        }

        return back()->with('success', 'Student attendance manually updated.');
    }

    /**
     * TODO: mark all present button
     */
    public function markAllPresent($id)
    {
        $session = ClassSession::findOrFail($id);

        $query = CourseEnrollment::where('course_id', $session->course_id)->where('role', 'student');
        if ($session->lab_id) {
            $query->where('lab_id', $session->lab_id);
        }
        $enrolledUserIds = $query->pluck('user_id');

        foreach ($enrolledUserIds as $userId) {
            AttendanceRecord::updateOrCreate(
                ['session_id' => $id, 'user_id' => $userId],
                [
                    'check_in_time' => now(),
                    'status' => 'on-time',
                    'checkin_method' => 'manual'
                ]
            );
        }

        return back()->with('success', 'All students marked as present.');
    }

    /**
     * TODO: clear all button
     */
    public function clearAll($id)
    {
        AttendanceRecord::where('session_id', $id)->delete();

        return back()->with('success', 'All attendance records cleared for this session.');
    }

    /**
     * TODO: export to excel (Using native CSV for lightweight export)
     */
    public function exportExcel($id)
    {
        $session = ClassSession::with('course')->findOrFail($id);
        $records = AttendanceRecord::with('user')->where('session_id', $id)->get();

        $fileName = 'attendance_' . $session->course->code . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Name', 'Student ID', 'Check-in Time', 'Status', 'Method'];

        $callback = function() use($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $record) {
                fputcsv($file, [
                    $record->user->name,
                    $record->user->student_id,
                    $record->check_in_time ? $record->check_in_time->format('Y-m-d H:i:s') : 'N/A',
                    $record->status,
                    $record->checkin_method
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
