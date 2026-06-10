<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminBroadcastController extends Controller
{
    public function index()
    {
        $pastBroadcasts = Notification::select('title', 'body', 'created_at', DB::raw('COUNT(*) as recipient_count'))
            ->where('type', 'broadcast')
            ->groupBy('title', 'body', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Broadcasts', [
            'pastBroadcasts' => $pastBroadcasts,
            'faculties' => Course::select('faculty')->whereNotNull('faculty')->distinct()->pluck('faculty')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target' => 'required|in:all_students,all_lecturers,all_users,specific_faculty',
            'faculty' => 'required_if:target,specific_faculty|string|nullable'
        ]);

        $query = User::query();

        if ($validated['target'] === 'all_students') {
            $query->where('role', 'student');
        } elseif ($validated['target'] === 'all_lecturers') {
            $query->where('role', 'lecturer');
        } elseif ($validated['target'] === 'specific_faculty') {
            $query->whereHas('courseEnrollments.course', function($q) use ($validated) {
                $q->where('faculty', $validated['faculty']);
            });
        }

        $users = $query->get();
        
        if ($users->isEmpty()) {
            return back()->withErrors(['target' => 'No users found matching the selected criteria.']);
        }

        $now = now();
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = [
                'user_id' => $user->id,
                'title' => $validated['title'],
                'body' => $validated['body'],
                'type' => 'broadcast',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks to avoid memory limit issues if there are thousands of users
        foreach (array_chunk($notifications, 500) as $chunk) {
            Notification::insert($chunk);
        }

        // Record in Audit Log
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'broadcast',
            'model_type' => Notification::class,
            'model_id' => 0, // General broadcast event
            'new_values' => [
                'title' => $validated['title'],
                'body' => $validated['body'],
                'target' => $validated['target'],
                'faculty' => $validated['faculty'],
                'recipient_count' => count($users)
            ]
        ]);

        return back()->with('success', 'Broadcast sent successfully to ' . count($users) . ' recipients.');
    }
}