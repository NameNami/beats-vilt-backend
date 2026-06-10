<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Programme;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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

        // 3. Arrival Breakdown (On-Time vs Late vs Absent)
        $records = AttendanceRecord::whereIn('session_id', $sessionIds)->get();

        $arrivalStats = [
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
            $courseSessionsCount = ClassSession::where('course_id', $enrollment->course_id)
                ->where('start_time', '<=', now())
                ->where('is_cancelled', false)
                ->count();

            if ($courseSessionsCount > 0) {
                // How many sessions did this student attend?
                $attendedCount = AttendanceRecord::where('user_id', $enrollment->user_id)
                    ->whereIn('session_id', ClassSession::where('course_id', $enrollment->course_id)->pluck('id'))
                    ->whereIn('status', ['present', 'on-time', 'late'])
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
                        'missed' => $courseSessionsCount - $attendedCount,
                    ];
                }
            }
        }

        return Inertia::render('Lecturer/AnalyticsDashboard', [
            'courses' => $courses,
            'totalSessions' => $totalSessions,
            'arrivalStats' => $arrivalStats,
            'atRiskStudents' => collect($atRiskStudents)->sortBy('percentage')->values()->all(),
        ]);
    }

    public function globalAnalytics(Request $request)
    {
        // 1. Get Semester Info
        $semesterInfo = $this->getSemesterInfo();

        // 2. Get Filtering Options
        $courses = Course::all();
        $programmes = Programme::all();

        // 3. Apply Filters
        $courseId = $request->input('course_id');
        $programmeId = $request->input('programme_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->getReportData($courseId, $programmeId, $startDate, $endDate);

        // --- Attendance Trend (Always 8 Points) ---
        $attendanceTrend = [];
        $semStart = Carbon::parse(SystemSetting::get('semester_start_date', '2026-03-04'))->startOfDay();
        $currentWeek = $semesterInfo['current_week'];

        // Always show 8 weeks. If current week is 12, show 5-12.
        // If current week is 2, show 1-8 (future weeks will be 0%).
        $endWeek = max(8, $currentWeek);
        $startWeek = max(1, $endWeek - 7);

        for ($i = $startWeek; $i <= $endWeek; $i++) {
            $weekStart = $semStart->copy()->addWeeks($i - 1)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weekSessions = ClassSession::where('is_completed', true)
                ->whereBetween('start_time', [$weekStart, $weekEnd])
                ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
                ->get();

            if ($weekSessions->isEmpty()) {
                $attendanceTrend[] = ['week' => "W$i", 'rate' => 0];

                continue;
            }

            $totalExpected = 0;
            $presentCount = 0;

            foreach ($weekSessions as $session) {
                // Expected students for this specific session
                $expected = CourseEnrollment::where('course_id', $session->course_id)
                    ->when($session->lab_id, fn ($q) => $q->where('lab_id', $session->lab_id))
                    ->where('role', 'student')
                    ->count();

                $totalExpected += $expected;

                $presentCount += AttendanceRecord::where('session_id', $session->id)
                    ->whereIn('status', ['on-time', 'late', 'present', 'leave'])
                    ->count();
            }

            $rate = $totalExpected > 0 ? round(($presentCount / $totalExpected) * 100, 1) : 0;
            $attendanceTrend[] = ['week' => "W$i", 'rate' => $rate];
        }

        return Inertia::render('Admin/AnalyticsDashboard', [
            'semesterInfo' => $semesterInfo,
            'courses' => $courses,
            'programmes' => $programmes,
            'filters' => [
                'course_id' => $courseId,
                'programme_id' => $programmeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'stats' => $data['stats'],
            'atRiskStudents' => $data['atRiskStudents'],
            'allStudents' => $data['allStudents'],
            'threshold' => (float) SystemSetting::get('min_attendance_threshold', 80),
            'attendanceTrend' => $attendanceTrend,
        ]);
    }

    private function getSemesterInfo()
    {
        $startDateStr = SystemSetting::get('semester_start_date', '2026-03-04');
        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $totalWeeks = (int) SystemSetting::get('semester_total_weeks', 14);

        $now = now()->startOfDay();

        if ($now->lt($startDate)) {
            $currentWeek = 0;
        } else {
            // Calculate week based on days to be more consistent
            $currentWeek = (int) ($startDate->diffInDays($now) / 7) + 1;
        }

        if ($currentWeek > $totalWeeks) {
            $currentWeek = $totalWeeks;
        }

        return [
            'semester' => SystemSetting::get('semester', '2025/2026-1'),
            'start_date' => $startDate->format('d M Y'),
            'total_weeks' => $totalWeeks,
            'current_week' => $currentWeek,
            'end_date' => $startDate->copy()->addWeeks($totalWeeks)->format('d M Y'),
        ];
    }

    private function getReportData($courseId = null, $programmeId = null, $startDate = null, $endDate = null)
    {
        $threshold = (float) SystemSetting::get('min_attendance_threshold', 80) / 100;

        // Base query for sessions
        $sessionQuery = ClassSession::where('is_completed', true);

        if ($courseId) {
            $sessionQuery->where('course_id', $courseId);
        }

        if ($startDate) {
            $sessionQuery->whereDate('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $sessionQuery->whereDate('start_time', '<=', $endDate);
        }

        $sessions = $sessionQuery->get();

        // Get relevant students
        $studentQuery = User::where('role', 'student');

        if ($courseId) {
            $studentQuery->whereHas('courseEnrollments', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        if ($programmeId) {
            $studentQuery->where('programme_id', $programmeId);
        }

        $students = $studentQuery->with(['programme', 'courseEnrollments.course'])->get();

        $allStudentsData = [];
        $atRiskStudents = [];

        $totalPresent = 0;
        $totalAbsent = 0;
        $totalLeave = 0;
        $totalOnTime = 0;
        $totalLate = 0;

        foreach ($students as $student) {
            $enrollments = $student->courseEnrollments;

            if ($courseId) {
                $enrollments = $enrollments->filter(fn ($e) => $e->course_id == $courseId);
            }

            foreach ($enrollments as $enrollment) {
                $studentPastSessionIds = ClassSession::where('course_id', $enrollment->course_id)
                    ->where('is_completed', true)
                    ->where(function ($q) use ($enrollment) {
                        $q->whereNull('lab_id')
                            ->orWhere('lab_id', $enrollment->lab_id);
                    })
                    ->when($startDate, fn ($q) => $q->whereDate('start_time', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('start_time', '<=', $endDate))
                    ->pluck('id');

                $total = $studentPastSessionIds->count();
                if ($total === 0) {
                    continue;
                }

                $records = AttendanceRecord::whereIn('session_id', $studentPastSessionIds)
                    ->where('user_id', $student->id)
                    ->get();

                $present = $records->whereIn('status', ['on-time', 'late', 'present', 'leave'])->count();
                $leave = $records->where('status', 'leave')->count();
                $absent = $total - $present;

                // For breakdowns
                $onTime = $records->whereIn('status', ['on-time', 'present'])->count();
                $late = $records->where('status', 'late')->count();

                $totalPresent += $present;
                $totalAbsent += $absent;
                $totalLeave += $leave;
                $totalOnTime += $onTime;
                $totalLate += $late;

                $rate = round(($present / $total) * 100, 1);
                $isAtRisk = ($present / $total) < $threshold;

                $studentEntry = [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'programme' => $student->programme->name ?? 'N/A',
                    'course_code' => $enrollment->course->code,
                    'present' => $present,
                    'absent' => $absent,
                    'leave' => $leave,
                    'on_time' => $onTime,
                    'late' => $late,
                    'total' => $total,
                    'rate' => $rate,
                    'is_at_risk' => $isAtRisk,
                ];

                $allStudentsData[] = $studentEntry;
                if ($isAtRisk) {
                    $atRiskStudents[] = $studentEntry;
                }
            }
        }

        $grandTotal = $totalPresent + $totalAbsent;
        $avgAttendance = $grandTotal > 0 ? round(($totalPresent / $grandTotal) * 100, 1) : 0;

        return [
            'stats' => [
                'avgAttendance' => $avgAttendance,
                'totalStudents' => $students->count(),
                'atRiskCount' => count($atRiskStudents),
                'breakdown' => [
                    'onTime' => $totalOnTime,
                    'late' => $totalLate,
                    'absent' => $totalAbsent,
                    'leave' => $totalLeave,
                    'total' => $grandTotal,
                ],
            ],
            'atRiskStudents' => $atRiskStudents,
            'allStudents' => $allStudentsData,
        ];
    }

    public function exportGlobalAnalytics(Request $request)
    {
        $courseId = $request->input('course_id');
        $programmeId = $request->input('programme_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->getReportData($courseId, $programmeId, $startDate, $endDate);

        $fileName = 'global_compliance_report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Student Name',
            'Student ID',
            'Programme',
            'Course Code',
            'On-Time',
            'Late',
            'Absent',
            'Approved Leave',
            'Total Expected Sessions',
            'Attendance Rate (%)',
            'Compliance Status',
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data['allStudents'] as $student) {
                $row = [
                    $student['name'],
                    $student['student_id'],
                    $student['programme'],
                    $student['course_code'],
                    $student['on_time'],
                    $student['late'],
                    $student['absent'],
                    $student['leave'],
                    $student['total'],
                    $student['rate'],
                    $student['is_at_risk'] ? 'AT-RISK' : 'COMPLIANT',
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
