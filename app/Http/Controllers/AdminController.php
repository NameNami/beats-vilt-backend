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
use Illuminate\Support\Facades\Hash;

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
        $sessions = ClassSession::with(['course', 'lab', 'lecturer', 'room'])
            ->orderBy('start_time', 'desc')
            ->get();

        return Inertia::render('Admin/ManageSessions', [
            'sessions' => $sessions,
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
            'lab_id' => 'required|exists:labs,id',
            'lecturer_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'mode' => 'required|in:online,physical',
            'checkin_method' => 'required|in:ble,qr,manual',
        ]);

        ClassSession::create($validated);

        return back()->with('success', 'Session created successfully.');
    }

    public function updateSession(Request $request, $id)
    {
        $session = ClassSession::findOrFail($id);
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'required|exists:labs,id',
            'lecturer_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'mode' => 'required|in:online,physical',
            'checkin_method' => 'required|in:ble,qr,manual',
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
    // =====================================================================
    // 7. USER MANAGEMENT (CRUD)
    // =====================================================================

    public function manageUsers()
    {
        // Fetch all users, order them by role, then by name for easy reading
        $users = User::orderBy('role')->orderBy('name')->get();

        return Inertia::render('Admin/ManageUsers', [
            'users' => $users
        ]);
    }

    /**
     * Bulk Import Students via CSV
     */
    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048', // Max 2MB CSV
        ]);

        $file = $request->file('file');

        // Open and read the CSV file natively
        $csvData = file_get_contents($file);
        $rows = array_map('str_getcsv', explode("\n", $csvData));

        // Remove the header row (Name, Email, Student ID)
        array_shift($rows);

        $importedCount = 0;

        foreach ($rows as $row) {
            // Ensure the row actually has 3 columns to prevent crashes
            if (count($row) >= 3) {
                $name = trim($row[0]);
                $email = trim($row[1]);
                $studentId = trim($row[2]);

                if ($name && $email && $studentId) {
                    // Extract a default username from email
                    $username = explode('@', $email)[0] . rand(10, 99);
                    
                    // UpdateOrCreate prevents duplicates! If email exists, it updates. If not, it creates.
                    User::updateOrCreate(
                        ['email' => $email],
                        [
                            'name' => $name,
                            'username' => $username,
                            'student_id' => $studentId,
                            'role' => 'student',
                            // FYP Trick: Set their default password to their Student ID!
                            'password' => \Illuminate\Support\Facades\Hash::make($studentId)
                        ]
                    );
                    $importedCount++;
                }
            }
        }

        return back()->with('success', "Successfully imported or updated {$importedCount} student accounts!");
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'student_id' => 'nullable|string|max:255|unique:users', // Used for student/staff login IDs
            'role' => 'required|in:admin,lecturer,student',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'student_id' => $request->student_id,
            'role' => $request->role,
            'password' => Hash::make($request->password), // Securely hash the password
        ]);

        return back()->with('success', 'User account created successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            // Ignore THIS user's current email/student_id when checking for uniqueness
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'student_id' => 'nullable|string|max:255|unique:users,student_id,' . $id,
            'role' => 'required|in:admin,lecturer,student',
            // Password is optional when updating
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->student_id = $request->student_id;
        $user->role = $request->role;

        // Only update the password if the admin typed a new one in
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'User account updated successfully.');
    }

    public function deleteUser($id)
    {
        // Safety check: Prevent the admin from deleting themselves
        if (auth()->id() == $id) {
            return back()->withErrors(['message' => 'You cannot delete your own active admin account.']);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'User account deleted successfully.');
    }
}
