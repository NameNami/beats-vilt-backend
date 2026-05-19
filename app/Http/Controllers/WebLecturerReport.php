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

class WebLecturerReport extends Controller
{
    public function index(Request $request)
    {
        $lecturerId = Auth::id();
        
        // 1. Get Semester Info
        $semesterInfo = $this->getSemesterInfo();

        // 2. Get Filtering Options
        $courses = Course::whereHas('enrollments', function ($query) use ($lecturerId) {
            $query->where('user_id', $lecturerId)->where('role', 'lecturer');
        })->get();

        $programmes = Programme::all();

        // 3. Apply Filters
        $courseId = $request->input('course_id');
        $programmeId = $request->input('programme_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->getReportData($lecturerId, $courseId, $programmeId, $startDate, $endDate);

        return Inertia::render('LecturerReport', [
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
            'threshold' => (float) SystemSetting::get('min_attendance_threshold', 80)
        ]);
    }

    public function exportCsv(Request $request)
    {
        $lecturerId = Auth::id();
        $courseId = $request->input('course_id');
        $programmeId = $request->input('programme_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->getReportData($lecturerId, $courseId, $programmeId, $startDate, $endDate);
        $students = $data['allStudents'];

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_report_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student ID', 'Name', 'Programme', 'Course', 'Early', 'On-Time', 'Late', 'Absent', 'Leave', 'Total Sessions', 'Rate (%)', 'Status']);

            foreach ($students as $student) {
                fputcsv($file, [
                    $student['student_id'],
                    $student['name'],
                    $student['programme'],
                    $student['course_code'],
                    $student['early'],
                    $student['on_time'],
                    $student['late'],
                    $student['absent'],
                    $student['leave'],
                    $student['total'],
                    $student['rate'],
                    $student['is_at_risk'] ? 'At Risk' : 'Good'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getSemesterInfo()
    {
        $startDateStr = SystemSetting::get('semester_start_date', '2026-03-04');
        $startDate = Carbon::parse($startDateStr);
        $totalWeeks = (int) SystemSetting::get('semester_total_weeks', 14);
        
        $now = now();
        $currentWeek = $now->diffInWeeks($startDate) + 1;
        if ($currentWeek < 1) $currentWeek = 0;
        if ($currentWeek > $totalWeeks) $currentWeek = $totalWeeks;

        return [
            'semester' => SystemSetting::get('semester', '2025/2026-1'),
            'start_date' => $startDate->format('d M Y'),
            'total_weeks' => $totalWeeks,
            'current_week' => $currentWeek,
            'end_date' => $startDate->copy()->addWeeks($totalWeeks)->format('d M Y')
        ];
    }

    private function getReportData($lecturerId, $courseId = null, $programmeId = null, $startDate = null, $endDate = null)
    {
        $threshold = (float) SystemSetting::get('min_attendance_threshold', 80) / 100;

        // Base query for sessions
        $sessionQuery = ClassSession::where('lecturer_id', $lecturerId)
            ->where('is_completed', true);

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
        $sessionIds = $sessions->pluck('id');

        // Get relevant students
        $studentQuery = User::where('role', 'student')
            ->whereHas('courseEnrollments', function($q) use ($lecturerId, $courseId) {
                $q->whereHas('course', function($cq) use ($lecturerId) {
                    $cq->whereHas('enrollments', function($eq) use ($lecturerId) {
                        $eq->where('user_id', $lecturerId)->where('role', 'lecturer');
                    });
                });
                if ($courseId) {
                    $q->where('course_id', $courseId);
                }
            });

        if ($programmeId) {
            $studentQuery->where('programme_id', $programmeId);
        }

        $students = $studentQuery->with(['programme', 'courseEnrollments.course'])->get();

        $allStudentsData = [];
        $atRiskStudents = [];
        
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalLeave = 0;
        $totalEarly = 0;
        $totalOnTime = 0;
        $totalLate = 0;

        foreach ($students as $student) {
            // Filter enrollments to match the lecturer/course scope
            $enrollments = $student->courseEnrollments->filter(function($e) use ($lecturerId, $courseId) {
                $isAssigned = CourseEnrollment::where('course_id', $e->course_id)
                    ->where('user_id', $lecturerId)
                    ->where('role', 'lecturer')
                    ->exists();
                return $isAssigned && (!$courseId || $e->course_id == $courseId);
            });

            foreach ($enrollments as $enrollment) {
                $studentPastSessionIds = ClassSession::where('course_id', $enrollment->course_id)
                    ->where('lecturer_id', $lecturerId)
                    ->where('is_completed', true)
                    ->where(function($q) use ($enrollment) {
                        $q->whereNull('lab_id')
                          ->orWhere('lab_id', $enrollment->lab_id);
                    })
                    ->when($startDate, fn($q) => $q->whereDate('start_time', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->whereDate('start_time', '<=', $endDate))
                    ->pluck('id');

                $total = $studentPastSessionIds->count();
                if ($total === 0) continue;

                $records = AttendanceRecord::whereIn('session_id', $studentPastSessionIds)
                    ->where('user_id', $student->id)
                    ->get();

                $present = $records->whereIn('status', ['early', 'on-time', 'late', 'present'])->count();
                $absent = $records->where('status', 'absent')->count();
                $leave = $records->where('status', 'leave')->count();
                
                // For breakdowns
                $early = $records->where('status', 'early')->count();
                $onTime = $records->where('status', 'on-time')->count();
                $late = $records->where('status', 'late')->count();

                $totalPresent += $present;
                $totalAbsent += $absent;
                $totalLeave += $leave;
                $totalEarly += $early;
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
                    'early' => $early,
                    'on_time' => $onTime,
                    'late' => $late,
                    'total' => $total,
                    'rate' => $rate,
                    'is_at_risk' => $isAtRisk
                ];

                $allStudentsData[] = $studentEntry;
                if ($isAtRisk) {
                    $atRiskStudents[] = $studentEntry;
                }
            }
        }

        $grandTotal = $totalPresent + $totalAbsent + $totalLeave;
        $avgAttendance = $grandTotal > 0 ? round(($totalPresent / $grandTotal) * 100, 1) : 0;

        return [
            'stats' => [
                'avgAttendance' => $avgAttendance,
                'totalStudents' => $students->count(),
                'atRiskCount' => count($atRiskStudents),
                'breakdown' => [
                    'early' => $totalEarly,
                    'onTime' => $totalOnTime,
                    'late' => $totalLate,
                    'absent' => $totalAbsent,
                    'leave' => $totalLeave,
                    'total' => $grandTotal
                ]
            ],
            'atRiskStudents' => $atRiskStudents,
            'allStudents' => $allStudentsData
        ];
    }
}
