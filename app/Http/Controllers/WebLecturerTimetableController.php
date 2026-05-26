<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Models\ClassSession;
use App\Models\SystemSetting;
use Carbon\Carbon;

class WebLecturerTimetableController extends Controller
{
    public function lecturerTimetable(Request $request)
    {
        $lecturerId = Auth::id();

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : now();
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $semesterStartDate = Carbon::parse(SystemSetting::get('semester_start_date', '2026-03-04'));
        $totalWeeks = (int) SystemSetting::get('semester_total_weeks', 14);
        $semesterEndDate = $semesterStartDate->copy()->addWeeks($totalWeeks)->endOfWeek(Carbon::SUNDAY);

        $currentWeek = (int) $semesterStartDate->diffInWeeks($startOfWeek) + 1;

        $sessions = ClassSession::with(['course', 'room', 'lab'])
            ->where('lecturer_id', $lecturerId)
            ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
            ->get();

        // Map unique courses to colors
        $courseIds = $sessions->pluck('course_id')->unique()->values();
        $availableColors = ['blue', 'emerald', 'purple', 'orange', 'rose', 'slate', 'indigo', 'cyan'];
        $courseColorMap = [];

        foreach ($courseIds as $index => $courseId) {
            $courseColorMap[$courseId] = $availableColors[$index % count($availableColors)];
        }

        $formattedSessions = $sessions->map(function ($session) use ($courseColorMap) {
            return [
                'id' => $session->id,
                'courseCode' => $session->course->code,
                'title' => $session->course->name,
                'day' => $session->start_time->format('l'),
                'date' => $session->start_time->toDateString(),
                'start' => $session->start_time->format('H:i'),
                'end' => $session->end_time->format('H:i'),
                'mode' => $session->mode,
                'lab' => $session->lab ? $session->lab->name : 'Lecture',
                'location' => $session->room ? $session->room->name : 'N/A',
                'instructor' => Auth::user()->name,
                'students' => $session->course->students()->count(),
                'color' => $courseColorMap[$session->course_id] ?? 'indigo',
                'isCancelled' => $session->is_cancelled,
                'isOngoing' => now()->between($session->start_time, $session->end_time) && !$session->is_cancelled,
            ];
        });

        return Inertia::render('LecturerTimetable', [
            'sessions' => $formattedSessions,
            'weekStartDate' => $startOfWeek->toDateString(),
            'currentWeek' => $currentWeek,
            'semesterStart' => $semesterStartDate->toDateString(),
            'semesterEnd' => $semesterEndDate->toDateString(),
        ]);
    }

    private function getModeColor($mode)
    {
        return match (strtolower($mode)) {
            'lecture' => 'blue',
            'tutorial' => 'emerald',
            'lab' => 'rose',
            'project' => 'orange',
            'co-curriculum' => 'slate',
            default => 'indigo',
        };
    }
}
