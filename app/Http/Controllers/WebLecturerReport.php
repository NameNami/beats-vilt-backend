<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\GamificationProfile;
use App\Models\LeaveApplication;
use App\Models\Programme;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // 3. Apply Filters
        $courseId = $request->input('course_id');
        
        // --- Enforce Single Course View ---
        if (!$courseId && $courses->isNotEmpty()) {
            $courseId = $courses->first()->id;
        }

        // Get only programmes that have students enrolled in this course for this lecturer
        $programmes = Programme::whereHas('users', function ($q) use ($courseId, $lecturerId) {
            $q->where('role', 'student')
              ->whereHas('courseEnrollments', function ($eq) use ($courseId, $lecturerId) {
                  $eq->where('course_id', $courseId)
                     ->whereHas('course', function ($cq) use ($lecturerId) {
                        $cq->whereHas('enrollments', function ($leq) use ($lecturerId) {
                            $leq->where('user_id', $lecturerId)->where('role', 'lecturer');
                        });
                     });
              });
        })->get();

        $programmeId = $request->input('programme_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->getReportData($lecturerId, $courseId, $programmeId, $startDate, $endDate);

        // --- Real Gamification Data ---
        
        // 1. Gamification Pulse
        $gamificationPulse = [
            'activeStreaks' => GamificationProfile::where('current_streak', '>', 0)->count(),
            'badgesAwarded' => DB::table('user_badges')->count(),
            'levelUps'      => GamificationProfile::whereNotNull('level_id')->count(), // Simple count of students who reached at least lvl 1
            'avgStudentLevel' => round(GamificationProfile::with('level')->get()->avg(fn($p) => $p->level->level ?? 1), 1)
        ];

        // 2. Leaderboard (Top 5 by XP - Students Only)
        $leaderboard = GamificationProfile::whereHas('user', function($q) {
                $q->where('role', 'student');
            })
            ->with(['user', 'level'])
            ->orderBy('total_xp', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($p, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $p->user->name,
                    'xp' => $p->total_xp,
                    'level' => $p->level->level ?? 1,
                    'streak' => $p->current_streak
                ];
            });

        // 3. Recent Badges (Latest 3)
        $recentBadges = DB::table('user_badges')
            ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
            ->join('users', 'user_badges.user_id', '=', 'users.id')
            ->select('badges.name', 'users.name as student', 'user_badges.created_at')
            ->orderBy('user_badges.created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => uniqid(),
                    'name' => $b->name,
                    'student' => $b->student,
                    'date' => Carbon::parse($b->created_at)->diffForHumans()
                ];
            });

        $gamificationHub = [
            'leaderboard' => $leaderboard,
            'recentBadges' => $recentBadges
        ];

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

            $weekSessions = ClassSession::where('lecturer_id', $lecturerId)
                ->where('is_completed', true)
                ->whereBetween('start_time', [$weekStart, $weekEnd])
                ->when($courseId, fn($q) => $q->where('course_id', $courseId))
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
                    ->when($session->lab_id, fn($q) => $q->where('lab_id', $session->lab_id))
                    ->where('role', 'student')
                    ->count();
                
                $totalExpected += $expected;

                $presentCount += AttendanceRecord::where('session_id', $session->id)
                    ->whereIn('status', ["early", "on-time", "late", "present"])
                    ->count();
            }

            $rate = $totalExpected > 0 ? round(($presentCount / $totalExpected) * 100, 1) : 0;
            $attendanceTrend[] = ['week' => "W$i", 'rate' => $rate];
        }

        // --- Pending Leaves ---
        $pendingLeaves = LeaveApplication::with(['user', 'classSession.course'])
            ->whereHas('classSession', function ($q) use ($lecturerId, $courseId) {
                $q->where('lecturer_id', $lecturerId)
                  ->when($courseId, fn($sq) => $sq->where('course_id', $courseId));
            })
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'avatar' => strtoupper(substr($l->user->name, 0, 1)),
                    'name' => $l->user->name,
                    'course' => $l->classSession->course->code,
                    'date' => $l->classSession->start_time->format('d M Y'),
                    'type' => $l->type,
                    'reason' => $l->reason
                ];
            });

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
            'threshold' => (float) SystemSetting::get('min_attendance_threshold', 80),
            'gamificationPulse' => $gamificationPulse,
            'attendanceTrend' => $attendanceTrend,
            'pendingLeaves' => $pendingLeaves,
            'gamificationHub' => $gamificationHub
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
        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $totalWeeks = (int) SystemSetting::get('semester_total_weeks', 14);
        
        $now = now()->startOfDay();
        
        if ($now->lt($startDate)) {
            $currentWeek = 0;
        } else {
            // Calculate week based on days to be more consistent
            $currentWeek = (int) ($startDate->diffInDays($now) / 7) + 1;
        }

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
                $leave = $records->where('status', 'leave')->count();
                $absent = $total - ($present + $leave);
                
                // For breakdowns
                $early = $records->where('status', 'early')->count();
                $onTime = $records->whereIn('status', ['on-time', 'present'])->count();
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
