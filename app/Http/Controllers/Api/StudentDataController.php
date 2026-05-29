<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassSession;
use App\Models\AttendanceRecord;
use App\Models\Notification;
use App\Models\LeaveApplication;
use App\Models\GamificationProfile;
use App\Models\Reward;
use App\Models\Redemption;
use Illuminate\Support\Facades\DB;

class StudentDataController extends Controller
{
    /**
     * Return list of available rewards.
     */
    public function getRewards(Request $request)
    {
        $rewards = Reward::where('is_active', true)->get();

        return response()->json([
            'status' => 'success',
            'data' => $rewards
        ], 200);
    }

    /**
     * Redeem a reward.
     */
    public function redeemReward(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        $reward = Reward::findOrFail($request->reward_id);
        $student = $request->user();
        $profile = $student->gamificationProfile;

        if (!$profile || $profile->total_points < $reward->cost_points) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient points.'
            ], 400);
        }

        if ($reward->stock <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reward out of stock.'
            ], 400);
        }

        DB::transaction(function () use ($reward, $student, $profile) {
            $profile->total_points -= $reward->cost_points;
            $profile->save();

            $reward->decrement('stock');

            Redemption::create([
                'user_id' => $student->id,
                'reward_id' => $reward->id,
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Reward redeemed successfully.'
        ], 201);
    }

    /**
     * Return the global leaderboard.
     */
    public function getLeaderboard(Request $request)
    {
        $leaderboard = GamificationProfile::with(['user.programme', 'level'])
            ->orderBy('total_xp', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $leaderboard
        ], 200);
    }

    /**
     * Submit a leave application.
     */
    public function submitLeave(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:class_sessions,id',
            'type' => 'required|string',
            'reason' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('leave_documents', 'public');
        }

        $leave = LeaveApplication::create([
            'user_id' => $request->user()->id,
            'session_id' => $request->session_id,
            'type' => $request->type,
            'reason' => $request->reason,
            'document_path' => $path,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave application submitted successfully.',
            'data' => $leave
        ], 201);
    }

    /**
     * Return the student's leave applications.
     */
    public function getLeaveApplications(Request $request)
    {
        $leaves = $request->user()->leaveApplications()
            ->with(['classSession.course', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $leaves
        ], 200);
    }

    /**
     * Return the student's profile including gamification data.
     */
    public function getProfile(Request $request)
    {
        $student = $request->user()->load(['gamificationProfile.level', 'badges', 'programme']);

        return response()->json([
            'status' => 'success',
            'data' => $student
        ], 200);
    }

    /**
     * Return the student's attendance history.
     */
    public function getAttendanceHistory(Request $request)
    {
        $attendance = $request->user()->attendanceRecords()
            ->with(['session.course', 'session.lecturer', 'session.room'])
            ->orderBy('check_in_time', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $attendance
        ], 200);
    }

    /**
     * Return the student's redemption history.
     */
    public function getRedemptions(Request $request)
    {
        $redemptions = $request->user()->redemptions()
            ->with('reward')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $redemptions
        ], 200);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markNotificationAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['status' => 'success', 'message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $request->user()->appNotifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['status' => 'success', 'message' => 'All notifications marked as read']);
    }

    /**
     * Return the student's notifications.
     */
    public function getNotifications(Request $request)
    {
        $notifications = $request->user()->appNotifications()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ], 200);
    }

    /**
     * Return the list of courses the student is enrolled in.
     */
    public function getCourses(Request $request)
    {
        // 1. Get the currently authenticated student
        $student = $request->user();

        // 2. Fetch their enrollments and eager load the actual Course and Lab details
        $enrollments = $student->courseEnrollments()
            ->with(['course', 'lab'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $enrollments
        ], 200);
    }

    /**
     * Return the student's class schedule with all related Foreign Key data.
     */
    public function getSchedule(Request $request)
    {
        $student = $request->user();

        // 1. Figure out exactly which courses and specific labs this student is in
        $enrollments = $student->courseEnrollments()->get();

        $courseIds = $enrollments->pluck('course_id');
        $labIds = $enrollments->whereNotNull('lab_id')->pluck('lab_id');

        // 2. Query the ClassSession model based on their enrollments
        $schedule = ClassSession::with(['course', 'lab', 'lecturer', 'room'])
            ->whereIn('course_id', $courseIds)
            ->where(function ($query) use ($labIds) {
                // A session applies to the student if it's a general lecture (lab_id is null)
                // OR if it's a specific lab session that matches their assigned lab_id
                $query->whereNull('lab_id')
                    ->orWhereIn('lab_id', $labIds);
            })
            // Optional: Only show upcoming classes (uncomment if needed)
            // ->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedule
        ], 200);
    }
}
