<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class WebAnalyticsController extends Controller
{
    public function lecturerAnalytics()
    {
        $lecturerId = Auth::id();

        // 1. Get all courses this lecturer teaches
        $courses = CourseEnrollment::where('user_id', $lecturerId)
            ->where('role', 'lecturer')
            ->with('course')
            ->get()
            ->pluck('course');

        $courseIds = $courses->pluck('id');

        // 2. Get all sessions for these courses
        $sessionIds = ClassSession::whereIn('course_id', $courseIds)->pluck('id');
        $totalSessions = $sessionIds->count();

        // 3. Arrival Breakdown (Early vs On-Time vs Late vs Absent)
        $records = AttendanceRecord::whereIn('session_id', $sessionIds)->get();

        $arrivalStats = [
            'Early' => $records->where('status', 'Early')->count(),
            'On-Time' => $records->where('status', 'On-Time')->count(),
            'Late' => $records->where('status', 'Late')->count(),
        ];

        // 4. Intervention Tracking (Find At-Risk Students < 80%)
        // Get all students enrolled in the lecturer's courses
        $studentEnrollments = CourseEnrollment::whereIn('course_id', $courseIds)
            ->where('role', 'student')
            ->with(['user', 'course'])
            ->get();

        $atRiskStudents = [];

        foreach ($studentEnrollments as $enrollment) {
            // How many sessions has this specific course held?
            $courseSessionsCount = ClassSession::where('course_id', $enrollment->course_id)->count();

            if ($courseSessionsCount > 0) {
                // How many sessions did this student attend?
                $attendedCount = AttendanceRecord::where('user_id', $enrollment->user_id)
                    ->whereIn('session_id', ClassSession::where('course_id', $enrollment->course_id)->pluck('id'))
                    ->count();

                $attendancePercentage = round(($attendedCount / $courseSessionsCount) * 100);

                // Flag if below 80%
                if ($attendancePercentage < 80) {
                    $atRiskStudents[] = [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'student_id' => $enrollment->user->student_id,
                        'course' => $enrollment->course->code,
                        'percentage' => $attendancePercentage,
                        'missed' => $courseSessionsCount - $attendedCount
                    ];
                }
            }
        }

        return Inertia::render('Lecturer/AnalyticsDashboard', [
            'courses' => $courses,
            'totalSessions' => $totalSessions,
            'arrivalStats' => $arrivalStats,
            'atRiskStudents' => collect($atRiskStudents)->sortBy('percentage')->values()->all()
        ]);
    }

    public function globalAnalytics()
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalCourses = Course::count();
        $totalSessions = ClassSession::count();

        // Find At-Risk Students (< 80% attendance) University-Wide
        $students = User::where('role', 'student')->get();
        $atRiskStudents = [];

        foreach ($students as $student) {
            // Get total sessions student should have attended (sessions for their enrolled courses)
            $enrolledCourseIds = CourseEnrollment::where('user_id', $student->id)
                ->where('role', 'student')
                ->pluck('course_id');

            $totalPossibleSessions = ClassSession::whereIn('course_id', $enrolledCourseIds)->count();

            if ($totalPossibleSessions > 0) {
                $attendedCount = AttendanceRecord::where('user_id', $student->id)->count();
                $percentage = round(($attendedCount / $totalPossibleSessions) * 100);

                if ($percentage < 80) {
                    $atRiskStudents[] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'student_id' => $student->student_id,
                        'percentage' => $percentage,
                        'missed_total' => $totalPossibleSessions - $attendedCount
                    ];
                }
            }
        }

        return Inertia::render('Admin/AnalyticsDashboard', [
            'totalStudents' => $totalStudents,
            'totalCourses' => $totalCourses,
            'totalSessions' => $totalSessions,
            'atRiskStudents' => collect($atRiskStudents)->sortBy('percentage')->values()->all()
        ]);
    }
}
