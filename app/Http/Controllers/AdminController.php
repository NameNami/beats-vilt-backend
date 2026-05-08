<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lab;
use App\Models\ClassSession;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * 1. Dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_lecturers' => User::where('role', 'lecturer')->count(),
            'total_courses' => Course::count(),
            'total_active_rooms' => Room::count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }

    /**
     * 2. Course Management
     */
    public function manageCourses()
    {
        return Inertia::render('Admin/ManageCourses', [
            'courses' => Course::with('labs')->get(),
            'lecturers' => User::where('role', 'lecturer')->get()
        ]);
    }

    public function storeCourse(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:courses,code',
            'name' => 'required|string',
            'faculty' => 'required|string',
        ]);

        Course::create($validated);

        return back()->with('success', 'Course created successfully.');
    }

    public function updateCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $validated = $request->validate([
            'code' => 'required|string|unique:courses,code,' . $id,
            'name' => 'required|string',
            'faculty' => 'required|string',
        ]);

        $course->update($validated);

        return back()->with('success', 'Course updated successfully.');
    }

    public function deleteCourse($id)
    {
        Course::findOrFail($id)->delete();
        return back()->with('success', 'Course deleted successfully.');
    }

    /**
     * 3. Lab Management
     */
    public function storeLab(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lecturer_id' => 'required|exists:users,id',
            'name' => 'required|string',
            'capacity' => 'required|integer',
        ]);

        Lab::create($validated);

        return back()->with('success', 'Lab created successfully.');
    }

    public function deleteLab($id)
    {
        Lab::findOrFail($id)->delete();
        return back()->with('success', 'Lab deleted successfully.');
    }

    /**
     * 4. Class Session Management
     */
    public function manageSessions()
    {
        return Inertia::render('Admin/ManageSessions', [
            'sessions' => ClassSession::with(['course', 'lab', 'lecturer', 'room'])->get(),
            'courses' => Course::all(),
            'labs' => Lab::all(),
            'lecturers' => User::where('role', 'lecturer')->get(),
            'rooms' => Room::all(),
        ]);
    }

    public function storeSession(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
            'lecturer_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'mode' => 'required|in:lecture,lab,tutorial',
            'checkin_method' => 'required|in:qr,beacon,manual',
        ]);

        ClassSession::create($validated);

        return back()->with('success', 'Session created successfully.');
    }

    public function updateSession(Request $request, $id)
    {
        $session = ClassSession::findOrFail($id);
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
            'lecturer_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'mode' => 'required|in:lecture,lab,tutorial',
            'checkin_method' => 'required|in:qr,beacon,manual',
        ]);

        $session->update($validated);

        return back()->with('success', 'Session updated successfully.');
    }

    public function deleteSession($id)
    {
        ClassSession::findOrFail($id)->delete();
        return back()->with('success', 'Session deleted successfully.');
    }

    /**
     * 5. Lecturer Management
     */
    public function manageLecturers()
    {
        $lecturers = User::where('role', 'lecturer')->with(['courseEnrollments.course'])->get();
        $availableCourses = Course::all();

        return Inertia::render('Admin/ManageLecturers', [
            'lecturers' => $lecturers,
            'availableCourses' => $availableCourses
        ]);
    }

    public function assignLecturer(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        CourseEnrollment::updateOrCreate(
            ['user_id' => $request->user_id, 'course_id' => $request->course_id, 'role' => 'lecturer'],
            ['enrolled_at' => now()]
        );

        return back()->with('success', 'Lecturer assigned successfully.');
    }

    public function removeLecturerAssignment($id)
    {
        CourseEnrollment::findOrFail($id)->delete();
        return back()->with('success', 'Assignment removed.');
    }

    /**
     * 6. Student Management
     */
    public function manageStudents()
    {
        $students = User::where('role', 'student')->with(['courseEnrollments.course', 'courseEnrollments.lab'])->get();
        return Inertia::render('Admin/ManageStudents', [
            'students' => $students,
            'availableCourses' => Course::all(),
            'availableLabs' => Lab::all()
        ]);
    }

    public function assignStudent(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
        ]);

        CourseEnrollment::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'lab_id' => $request->lab_id,
            'role' => 'student',
            'enrolled_at' => now(),
        ]);

        return back()->with('success', 'Student enrolled successfully.');
    }
}
