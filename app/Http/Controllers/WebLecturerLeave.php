<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebLecturerLeave extends Controller
{
    public function index()
    {
        $lecturerId = Auth::id();

        $applications = LeaveApplication::with(['user', 'classSession.course'])
            ->whereHas('classSession', function ($query) use ($lecturerId) {
                $query->where('lecturer_id', $lecturerId);
            })
            ->latest()
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'studentName' => $app->user->name,
                    'studentId' => $app->user->student_id,
                    'courseCode' => $app->classSession->course->code ?? 'N/A',
                    'courseName' => $app->classSession->course->name ?? 'N/A',
                    'sessionDate' => $app->classSession->start_time->format('d M Y'),
                    'sessionTime' => $app->classSession->start_time->format('H:i') . ' - ' . $app->classSession->end_time->format('H:i'),
                    'leaveType' => $app->type,
                    'reason' => $app->reason,
                    'hasDocument' => (bool)$app->document_path,
                    'documentUrl' => $app->document_path ? asset('storage/' . $app->document_path) : null,
                    'status' => $app->status,
                    'submittedAt' => $app->created_at->format('d M Y, H:i A'),
                    'reviewedAt' => $app->reviewed_at ? $app->reviewed_at->format('d M Y, H:i A') : null,
                ];
            });

        return Inertia::render('LecturerLeave', [
            'initialApplications' => $applications,
        ]);
    }

    public function updateStatus(Request $request, LeaveApplication $application)
    {
        // Ensure the lecturer owns this session
        if ($application->classSession->lecturer_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $application->update([
            'status' => $request->status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => $request->status === 'pending' ? null : now(),
        ]);

        return back()->with('success', 'Leave application status updated.');
    }
}
