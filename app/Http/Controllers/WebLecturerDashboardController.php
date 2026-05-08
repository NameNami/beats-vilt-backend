<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\CourseEnrollment;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebLecturerDashboardController extends Controller
{
    public function lecturerDashboard()
    {
        $lecturerId = Auth::id();

        // 1. Class Today Count
        $classTodayCount = ClassSession::where('lecturer_id', $lecturerId)
            ->whereDate('start_time', Carbon::today())
            ->count();

        // 2. Pending Leave Count
        $pendingLeaveCount = LeaveApplication::where('status', 'pending')
            ->whereHas('classSession', function ($query) use ($lecturerId) {
                $query->where('lecturer_id', $lecturerId);
            })
            ->count();

        // 3. Overall Attendance & At-Risk Students
        $completedSessions = ClassSession::where('lecturer_id', $lecturerId)
            ->where('is_completed', true)
            ->get();

        $totalExpected = 0;
        $totalPresent = 0;
        $studentAttendance = [];

        foreach ($completedSessions as $session) {
            $enrollments = CourseEnrollment::where('course_id', $session->course_id)
                ->when($session->lab_id, function ($query) use ($session) {
                    return $query->where('lab_id', $session->lab_id);
                })
                ->where('role', 'student')
                ->get();

            $expectedCount = $enrollments->count();
            $totalExpected += $expectedCount;

            $presentStudentIds = AttendanceRecord::where('session_id', $session->id)
                ->whereIn('status', ['early', 'on-time', 'late', 'present'])
                ->pluck('user_id')
                ->toArray();

            $totalPresent += count($presentStudentIds);

            foreach ($enrollments as $enrollment) {
                if (!isset($studentAttendance[$enrollment->user_id])) {
                    $studentAttendance[$enrollment->user_id] = ['total' => 0, 'present' => 0];
                }
                $studentAttendance[$enrollment->user_id]['total']++;
                if (in_array($enrollment->user_id, $presentStudentIds)) {
                    $studentAttendance[$enrollment->user_id]['present']++;
                }
            }
        }

        $overallAttendance = $totalExpected > 0 ? round(($totalPresent / $totalExpected) * 100, 1) : 0;

        $atRiskStudentCount = 0;
        foreach ($studentAttendance as $stats) {
            $rate = $stats['total'] > 0 ? ($stats['present'] / $stats['total']) : 1;
            if ($rate < 0.8) {
                $atRiskStudentCount++;
            }
        }

        // Fetch Today's Schedule
        $scheduleItems = ClassSession::with(['course', 'lab', 'room'])
            ->where('lecturer_id', $lecturerId)
            ->whereDate('start_time', Carbon::today())
            ->orderBy('start_time')
            ->get()
            ->map(function ($session) {
                // Determine status
                $now = now();
                if ($session->is_cancelled) {
                    $status = 'cancelled';
                } elseif ($session->is_completed) {
                    $status = 'completed';
                } elseif ($now->lt($session->start_time)) {
                    $status = 'upcoming';
                } elseif ($now->between($session->start_time, $session->end_time)) {
                    $status = 'ongoing';
                } else {
                    // Past end time but not marked completed or cancelled
                    $status = 'past';
                }

                return [
                    'id' => $session->id,
                    'time' => $session->start_time->format('H:i'),
                    'end_time' => $session->end_time->format('H:i'),
                    'code' => $session->course->code ?? 'N/A',
                    'title' => $session->course->name ?? 'N/A',
                    'location' => ($session->lab->name ?? ($session->room->name ?? 'N/A')),
                    'students' => CourseEnrollment::where('course_id', $session->course_id)
                        ->when($session->lab_id, function ($query) use ($session) {
                            return $query->where('lab_id', $session->lab_id);
                        })
                        ->where('role', 'student')
                        ->count(),
                    'status' => $status,
                    'is_cancelled' => (bool)$session->is_cancelled,
                    'is_in_class_time' => $now->between($session->start_time, $session->end_time),
                ];
            });

        return Inertia::render('LecturerDashboard', [
            'overallAttendance' => (string)$overallAttendance,
            'classTodayCount' => (string)$classTodayCount,
            'pendingLeaveCount' => (string)$pendingLeaveCount,
            'atRiskStudentCount' => (string)$atRiskStudentCount,
            'scheduleItems' => $scheduleItems,
        ]);
    }

    public function toggleCancel(ClassSession $session)
    {
        if ($session->lecturer_id !== Auth::id()) {
            abort(403);
        }

        $session->is_cancelled = !$session->is_cancelled;
        $session->save();

        return back();
    }

    public function averageAttendance()
    {

    }
}
