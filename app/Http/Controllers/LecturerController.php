<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ClassSession;

class LecturerController extends Controller
{
    /**
     * Display the lecturer's main dashboard with their courses and schedule.
     */
    public function dashboard(Request $request)
    {
        $lecturer = $request->user();

        // 1. Fetch courses where this user is enrolled specifically as a lecturer
        $enrollments = $lecturer->courseEnrollments()
            ->where('role', 'lecturer') // Filtering based on the enum role [cite: 59]
            ->with('course')
            ->get();

        // Extract just the course objects to keep the frontend clean
        $courses = $enrollments->pluck('course');

        // 2. Fetch the lecturer's upcoming class schedule
        $schedule = ClassSession::with(['course', 'lab', 'room'])
            ->where('lecturer_id', $lecturer->id) // Using the direct lecturer_id FK
            ->orderBy('start_time', 'asc')
            ->get();

        // 3. Render the Inertia Vue component and pass the data to it
        return Inertia::render('Lecturer/Dashboard', [
            'lecturer' => $lecturer,
            'courses' => $courses,
            'schedule' => $schedule
        ]);
    }
}
