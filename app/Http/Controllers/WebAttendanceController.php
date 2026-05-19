<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\ClassSession;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\QrToken;

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
        $semesterStartDate = Carbon::parse(SystemSetting::get('semester_start_date', '2026-03-02'));

        $sessions = ClassSession::with(['course', 'lab', 'room'])
            ->where('lecturer_id', $lecturerId)
            ->get()
            ->map(function ($session) use ($semesterStartDate) {
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

                // Calculate Week
                $sessionStartOfWeek = $session->start_time->copy()->startOfWeek(Carbon::MONDAY);
                $week = (int) $semesterStartDate->diffInWeeks($sessionStartOfWeek) + 1;

                return [
                    'id' => $session->id,
                    'course_id' => $session->course_id,
                    'lab' => $session->lab->name ?? null,
                    'date' => $session->start_time->format('d M Y'),
                    'day' => $session->start_time->format('l'),
                    'time' => $session->start_time->format('H:i') . ' - ' . $session->end_time->format('H:i'),
                    'location' => $session->room->name ?? 'N/A',
                    'status' => $status,
                    'week' => $week,
                ];
            });

        // 3. At-Risk Students Calculation
        $thresholdValue = (float) SystemSetting::get('min_attendance_threshold', 80);
        $threshold = $thresholdValue / 100;

        $atRiskStudents = [];

        foreach ($courses as $courseData) {
            $courseId = $courseData['id'];

            // Get all students enrolled in this course
            $enrolledStudents = User::whereHas('courseEnrollments', function($q) use ($courseId) {
                $q->where('course_id', $courseId)->where('role', 'student');
            })->with(['courseEnrollments' => function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            }])->get();

            foreach ($enrolledStudents as $student) {
                $studentEnrollment = $student->courseEnrollments->first();

                // Find sessions this student was expected to attend for this lecturer
                $studentPastSessionIds = ClassSession::where('course_id', $courseId)
                    ->where('lecturer_id', $lecturerId)
                    ->where('is_completed', true)
                    ->where(function($q) use ($studentEnrollment) {
                        $q->whereNull('lab_id') // Lecture (all students)
                          ->orWhere('lab_id', $studentEnrollment->lab_id); // Their specific lab
                    })
                    ->pluck('id');

                $totalPast = $studentPastSessionIds->count();
                if ($totalPast === 0) continue;

                $presentCount = AttendanceRecord::whereIn('session_id', $studentPastSessionIds)
                    ->where('user_id', $student->id)
                    ->whereIn('status', ['early', 'on-time', 'late', 'present'])
                    ->count();

                $rate = $presentCount / $totalPast;
                if ($rate < $threshold) {
                    $atRiskStudents[] = [
                        'id' => $student->id,
                        'student_id' => $student->student_id,
                        'name' => $student->name,
                        'course_name' => $courseData['name'],
                        'course_code' => $courseData['code'],
                        'attendance_rate' => round($rate * 100, 1),
                        'present_count' => $presentCount,
                        'total_count' => $totalPast
                    ];
                }
            }
        }

        return Inertia::render('LecturerAttendance', [
            'courses' => $courses,
            'sessions' => $sessions,
            'atRiskCount' => count($atRiskStudents),
            'atRiskStudents' => $atRiskStudents,
            'threshold' => $thresholdValue
        ]);
    }

    public function show(ClassSession $session)
    {
        $session->load(['course.students', 'lab', 'room', 'attendanceRecords.user']);

        $thresholdValue = (float) SystemSetting::get('min_attendance_threshold', 80);
        $threshold = $thresholdValue / 100;

        $lecturerId = Auth::id();

        // Get all students enrolled in the course
        $students = $session->course->students->map(function ($student) use ($session, $threshold, $lecturerId) {
            $record = $session->attendanceRecords->firstWhere('user_id', $student->id);
            $rawStatus = $record ? $record->status : 'absent';

            // Normalize status for UI toggle
            $uiStatus = in_array($rawStatus, ['early', 'on-time', 'late', 'present']) ? 'present' : $rawStatus;

            // At-risk calculation for this student
            // We must find their specific enrollment to know their lab_id
            $studentEnrollment = CourseEnrollment::where('user_id', $student->id)
                ->where('course_id', $session->course_id)
                ->first();

            $studentPastSessionIds = ClassSession::where('course_id', $session->course_id)
                ->where('lecturer_id', $lecturerId)
                ->where('is_completed', true)
                ->where(function($q) use ($studentEnrollment) {
                    $q->whereNull('lab_id')
                      ->orWhere('lab_id', $studentEnrollment->lab_id);
                })
                ->pluck('id');

            $totalPast = $studentPastSessionIds->count();
            $presentPast = AttendanceRecord::whereIn('session_id', $studentPastSessionIds)
                ->where('user_id', $student->id)
                ->whereIn('status', ['early', 'on-time', 'late', 'present'])
                ->count();

            $rate = $totalPast > 0 ? ($presentPast / $totalPast) : 1;
            $isAtRisk = $rate < $threshold;

            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'status' => $uiStatus,
                'is_at_risk' => $isAtRisk,
                'attendance_rate' => round($rate * 100, 1)
            ];
        })->sort(function ($a, $b) {
            // Sort by is_at_risk descending, then by name
            if ($a['is_at_risk'] === $b['is_at_risk']) {
                return strcmp($a['name'], $b['name']);
            }
            return $b['is_at_risk'] <=> $a['is_at_risk'];
        })->values();

        $activeQrToken = $session->activeQrToken;

        return response()->json([
            'session' => [
                'id' => $session->id,
                'course_name' => $session->course->name,
                'course_code' => $session->course->code,
                'mode' => $session->mode,
                'lab' => $session->lab->name ?? 'Lecture',
                'location' => $session->room->name ?? 'N/A',
                'start_time' => $session->start_time->format('H:i'),
                'end_time' => $session->end_time->format('H:i'),
                'is_display' => $session->is_display,
                'qr_token' => $activeQrToken ? $activeQrToken->token : null,
            ],
            'students' => $students,
            'stats' => [
                'present' => $students->where('status', 'present')->count(),
                'late' => $students->where('status', 'late')->count(),
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

    public function generateQr(ClassSession $session)
    {
        $now = now();
        $expiresAt = null;

        if ($now->lt($session->end_time)) {
            // During class: Check for existing active token
            $existingToken = $session->activeQrToken;
            if ($existingToken && $existingToken->expires_at->eq($session->end_time)) {
                return response()->json(['token' => $existingToken->token]);
            }
            $expiresAt = $session->end_time;
        } else {
            // After class: 15-minute rolling expiration
            $expiresAt = $now->addMinutes(15);
        }

        $token = QrToken::create([
            'session_id' => $session->id,
            'token' => Str::random(64),
            'expires_at' => $expiresAt,
        ]);

        return response()->json(['token' => $token->token]);
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
        $studentIds = $session->course->students->pluck('id');

        foreach ($studentIds as $userId) {
            $session->attendanceRecords()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'status' => 'absent',
                    'check_in_time' => now(),
                    'checkin_method' => 'manual'
                ]
            );
        }

        return response()->json(['success' => true]);
    }
}
