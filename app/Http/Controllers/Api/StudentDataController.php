<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassSession;

class StudentDataController extends Controller
{
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
