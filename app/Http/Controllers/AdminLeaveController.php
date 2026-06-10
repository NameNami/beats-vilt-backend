<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLeaveController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $students = User::where('role', 'student')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'student_id')
            ->orderBy('name')
            ->take(50) // Limit to prevent massive payloads if no search is provided
            ->get();

        $recentLeaves = LeaveApplication::with(['user:id,name,student_id', 'reviewer:id,name'])
            ->where('status', 'approved')
            ->whereNotNull('reviewed_by') // Only show admin-approved or lecturer-approved leaves
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'student_name' => $leave->user->name,
                    'student_id' => $leave->user->student_id,
                    'type' => $leave->type,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'created_at' => $leave->created_at,
                    'reviewer_name' => $leave->reviewer ? $leave->reviewer->name : 'System',
                ];
            });

        return Inertia::render('Admin/LeaveManagement', [
            'students' => $students,
            'recentLeaves' => $recentLeaves,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:medical,emergency,other',
            'reason' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $student = User::findOrFail($validated['user_id']);

        // Find all sessions this student is supposed to attend between the dates
        // A student is expected to attend a session if they are enrolled in the course,
        // and either the session has no lab, or the session's lab matches their enrollment's lab.

        $sessions = ClassSession::whereBetween('start_time', [
            Carbon::parse($validated['start_date'])->startOfDay(),
            Carbon::parse($validated['end_date'])->endOfDay(),
        ])
            ->whereHas('course.enrollments', function ($query) use ($student) {
                $query->where('user_id', $student->id)
                    ->where('role', 'student');
            })
            ->get()
            ->filter(function ($session) use ($student) {
                // Get the student's enrollment for this specific course
                $enrollment = $student->courseEnrollments()->where('course_id', $session->course_id)->first();
                if (! $enrollment) {
                    return false;
                }

                // If session is a full lecture (no lab), they must attend
                if (is_null($session->lab_id)) {
                    return true;
                }

                // If session is a lab, they only attend if their assigned lab matches
                return $session->lab_id === $enrollment->lab_id;
            });

        if ($sessions->isEmpty()) {
            return back()->withErrors(['message' => 'No scheduled classes found for this student during the selected date range.']);
        }

        $adminId = auth()->id();
        $leavesCreated = 0;

        foreach ($sessions as $session) {
            // Check if a leave application already exists for this exact session
            $existing = LeaveApplication::where('user_id', $student->id)
                ->where('session_id', $session->id)
                ->first();

            if (! $existing) {
                LeaveApplication::create([
                    'user_id' => $student->id,
                    'session_id' => $session->id,
                    'type' => $validated['type'],
                    'reason' => 'Global Admin Override: '.$validated['reason'],
                    'status' => 'approved',
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);

                // If there's an existing attendance record for this session, update it to 'leave'
                AttendanceRecord::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'user_id' => $student->id,
                    ],
                    [
                        'status' => 'leave',
                        'scanned_at' => now(),
                    ]
                );

                $leavesCreated++;
            } else {
                // If it exists but is pending or rejected, forcefully approve it
                if ($existing->status !== 'approved') {
                    $existing->update([
                        'status' => 'approved',
                        'reason' => 'Global Admin Override: '.$validated['reason'],
                        'reviewed_by' => $adminId,
                        'reviewed_at' => now(),
                    ]);

                    AttendanceRecord::updateOrCreate(
                        [
                            'session_id' => $session->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'status' => 'leave',
                            'scanned_at' => now(),
                        ]
                    );

                    $leavesCreated++;
                }
            }
        }

        return back()->with('success', "Successfully applied pre-approved leave to {$leavesCreated} class sessions.");
    }
}
