<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebAttendanceController extends Controller
{
    public function index()
    {
        $lecturerId = Auth::id();

        // 1. Get Courses the lecturer is teaching
        $courses = Course::whereHas('enrollments', function ($query) use ($lecturerId) {
            $query->where('user_id', $lecturerId)->where('role', 'lecturer');
        })
        ->withCount(['students'])
        ->get()
        ->map(function ($course) {
            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'enrolled' => $course->students_count,
            ];
        });

        // 2. Get All Sessions for this lecturer
        $sessions = ClassSession::with(['course', 'lab', 'room'])
            ->where('lecturer_id', $lecturerId)
            ->get()
            ->map(function ($session) {
                // Determine Status
                $now = now();
                $status = 'upcoming';
                if ($session->is_cancelled) {
                    $status = 'cancelled';
                } elseif ($session->is_completed) {
                    $status = 'completed';
                } elseif ($now->between($session->start_time, $session->end_time)) {
                    $status = 'active';
                } elseif ($now->gt($session->end_time)) {
                    $status = 'completed';
                }

                return [
                    'id' => $session->id,
                    'course_id' => $session->course_id,
                    'lab' => $session->lab->name ?? null,
                    'date' => $session->start_time->format('d M Y'),
                    'time' => $session->start_time->format('H:i') . ' - ' . $session->end_time->format('H:i'),
                    'location' => $session->lab->name ?? ($session->room->name ?? 'N/A'),
                    'status' => $status,
                ];
            });

        return Inertia::render('LecturerAttendance', [
            'courses' => $courses,
            'sessions' => $sessions,
        ]);
    }

    public function show(ClassSession $session)
    {
        $session->load(['course.students', 'lab', 'room', 'attendanceRecords.user']);

        // Get all students enrolled in the course
        $students = $session->course->students->map(function ($student) use ($session) {
            $record = $session->attendanceRecords->firstWhere('user_id', $student->id);
            $rawStatus = $record ? $record->status : 'absent';
            
            // Normalize status for UI toggle (early, on-time, late, present all count as 'present')
            $uiStatus = in_array($rawStatus, ['early', 'on-time', 'late', 'present']) ? 'present' : $rawStatus;

            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'status' => $uiStatus,
            ];
        });

        $activeQrToken = $session->activeQrToken;

        return response()->json([
            'session' => [
                'id' => $session->id,
                'course_name' => $session->course->name,
                'course_code' => $session->course->code,
                'mode' => $session->mode,
                'location' => $session->lab->name ?? ($session->room->name ?? 'N/A'),
                'start_time' => $session->start_time->format('H:i'),
                'end_time' => $session->end_time->format('H:i'),
                'is_display' => $session->is_display,
                'qr_token' => $activeQrToken ? $activeQrToken->token : null,
            ],
            'students' => $students,
            'stats' => [
                'present' => $students->where('status', 'present')->count(),
                'absent' => $students->where('status', 'absent')->count(),
                'leave' => $students->where('status', 'leave')->count(),
                'total' => $students->count(),
            ]
        ]);
    }

    public function toggleDisplay(Request $request, ClassSession $session)
    {
        $session->update([
            'is_display' => $request->is_display
        ]);

        return response()->json(['success' => true]);
    }

    public function markAttendance(Request $request, ClassSession $session)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,leave'
        ]);

        $session->attendanceRecords()->updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'status' => $request->status,
                'check_in_time' => now(),
                'checkin_method' => 'manual'
            ]
        );

        return response()->json(['success' => true]);
    }

    public function markAllPresent(ClassSession $session)
    {
        $studentIds = $session->course->students->pluck('id');

        foreach ($studentIds as $userId) {
            $session->attendanceRecords()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'status' => 'present',
                    'check_in_time' => now(),
                    'checkin_method' => 'manual'
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    public function resetAttendance(ClassSession $session)
    {
        $session->attendanceRecords()->delete();

        return response()->json(['success' => true]);
    }
}
