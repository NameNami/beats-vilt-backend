<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lab;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
            'courses' => Course::with(['enrollments' => function($q) {
                $q->where('role', 'student');
            }, 'labs.lecturer', 'labs.enrollments' => function($q) {
                $q->where('role', 'student');
            }])->get(),
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

    public function updateLab(Request $request, $id)
    {
        $lab = Lab::findOrFail($id);
        $validated = $request->validate([
            'lecturer_id' => 'required|exists:users,id',
            'name' => 'required|string',
            'capacity' => 'required|integer',
        ]);

        $lab->update($validated);

        return back()->with('success', 'Lab updated successfully.');
    }

    public function deleteLab($id)
    {
        Lab::findOrFail($id)->delete();
        return back()->with('success', 'Lab deleted successfully.');
    }

    /**
     * 4. Class Session Management
     */
    public function manageSessions(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : now();
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $courseIdFilter = $request->input('course_id');
        $facultyFilter = $request->input('faculty');

        $semesterStartDate = Carbon::parse(SystemSetting::get('semester_start_date', '2026-03-04'));
        $totalWeeks = (int) SystemSetting::get('semester_total_weeks', 14);
        $semesterEndDate = $semesterStartDate->copy()->addWeeks($totalWeeks)->endOfWeek(Carbon::SUNDAY);

        $currentWeek = (int) $semesterStartDate->diffInWeeks($startOfWeek) + 1;

        $query = ClassSession::with(['course', 'lab', 'lecturer', 'room'])
            ->whereBetween('start_time', [$startOfWeek, $endOfWeek]);

        if ($courseIdFilter) {
            $query->where('course_id', $courseIdFilter);
        }

        if ($facultyFilter) {
            $query->whereHas('course', function($q) use ($facultyFilter) {
                $q->where('faculty', $facultyFilter);
            });
        }

        $sessions = $query->get();

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
                'instructor' => $session->lecturer?->name ?? 'N/A',
                'students' => $session->course->students()->count(),
                'color' => $courseColorMap[$session->course_id] ?? 'indigo',
                'isCancelled' => $session->is_cancelled,
                'isOngoing' => now()->between($session->start_time, $session->end_time) && !$session->is_cancelled,

                // CRUD fields
                'course_id' => $session->course_id,
                'lab_id' => $session->lab_id,
                'lecturer_id' => $session->lecturer_id,
                'room_id' => $session->room_id,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'checkin_method' => $session->checkin_method,
            ];
        });

        return Inertia::render('Admin/ManageSessions', [
            'sessions' => $formattedSessions,
            'courses' => Course::all(),
            'labs' => Lab::all(),
            'lecturers' => User::where('role', 'lecturer')->get(),
            'rooms' => Room::all(),
            'weekStartDate' => $startOfWeek->toDateString(),
            'currentWeek' => $currentWeek,
            'semesterStart' => $semesterStartDate->toDateString(),
            'semesterEnd' => $semesterEndDate->toDateString(),
            'filters' => [
                'course_id' => $courseIdFilter,
                'faculty' => $facultyFilter,
            ]
        ]);
    }

    public function storeSession(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
            'lecturer_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'mode' => 'required|in:online,physical',
            'checkin_method' => 'required|in:ble,qr,manual',
            'is_recurring' => 'boolean',
        ]);

        $sessionsToCreate = [];
        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);

        $isRecurring = $request->boolean('is_recurring');
        unset($validated['is_recurring']);

        if ($isRecurring) {
            $semesterEndDate = Carbon::parse(SystemSetting::get('semester_end_date', '2026-06-20'))->endOfDay();
            
            $currentStart = $startTime->copy();
            $currentEnd = $endTime->copy();

            while ($currentStart->lte($semesterEndDate)) {
                $sessionsToCreate[] = [
                    'start' => $currentStart->toDateTimeString(),
                    'end' => $currentEnd->toDateTimeString(),
                ];
                $currentStart->addWeek();
                $currentEnd->addWeek();
            }
        } else {
            $sessionsToCreate[] = [
                'start' => $startTime->toDateTimeString(),
                'end' => $endTime->toDateTimeString(),
            ];
        }

        $createdCount = 0;
        $conflicts = [];

        foreach ($sessionsToCreate as $slot) {
            $conflict = null;
            
            // Only check for conflicts if we have a room, lecturer, or lab to check against
            if (!empty($validated['room_id']) || !empty($validated['lecturer_id']) || !empty($validated['lab_id'])) {
                $conflict = ClassSession::where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        if (!empty($validated['room_id'])) {
                            $q->orWhere('room_id', $validated['room_id']);
                        }
                        if (!empty($validated['lecturer_id'])) {
                            $q->orWhere('lecturer_id', $validated['lecturer_id']);
                        }
                        if (!empty($validated['lab_id'])) {
                            $q->orWhere('lab_id', $validated['lab_id']);
                        }
                    });
                })->where(function ($query) use ($slot) {
                    $query->whereBetween('start_time', [$slot['start'], $slot['end']])
                          ->orWhereBetween('end_time', [$slot['start'], $slot['end']])
                          ->orWhere(function ($q) use ($slot) {
                              $q->where('start_time', '<=', $slot['start'])
                                ->where('end_time', '>=', $slot['end']);
                          });
                })->first();
            }

            if ($conflict) {
                $dateStr = Carbon::parse($slot['start'])->format('d M');
                $conflicts[] = "Conflict on {$dateStr}";
                continue;
            }

            ClassSession::create(array_merge($validated, [
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
            ]));
            $createdCount++;
        }

        if (count($conflicts) > 0 && $createdCount === 0) {
            return back()->withErrors(['conflict' => 'Could not create sessions. All dates had conflicts: ' . implode(', ', $conflicts)]);
        }

        $msg = "Successfully created {$createdCount} sessions.";
        if (count($conflicts) > 0) {
            $msg .= " Skipped " . count($conflicts) . " due to conflicts: " . implode(', ', $conflicts);
        }

        return back()->with('success', $msg);
    }

    public function updateSession(Request $request, $id)
    {
        $session = ClassSession::findOrFail($id);
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
            'lecturer_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'mode' => 'required|in:online,physical',
            'checkin_method' => 'required|in:ble,qr,manual',
        ]);

        $conflict = null;
        if (!empty($validated['room_id']) || !empty($validated['lecturer_id']) || !empty($validated['lab_id'])) {
            $conflict = ClassSession::where('id', '!=', $id)
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        if (!empty($validated['room_id'])) {
                            $q->orWhere('room_id', $validated['room_id']);
                        }
                        if (!empty($validated['lecturer_id'])) {
                            $q->orWhere('lecturer_id', $validated['lecturer_id']);
                        }
                        if (!empty($validated['lab_id'])) {
                            $q->orWhere('lab_id', $validated['lab_id']);
                        }
                    });
                })->where(function ($query) use ($validated) {
                    $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('start_time', '<=', $validated['start_time'])
                                ->where('end_time', '>=', $validated['end_time']);
                          });
                })->first();
        }

        if ($conflict) {
            $conflictReason = [];
            if ($conflict->room_id == $validated['room_id']) $conflictReason[] = 'Room';
            if ($conflict->lecturer_id == $validated['lecturer_id']) $conflictReason[] = 'Lecturer';
            if ($conflict->lab_id == $validated['lab_id']) $conflictReason[] = 'Lab Group';
            
            return back()->withErrors(['conflict' => 'Scheduling conflict detected for: ' . implode(', ', $conflictReason) . '. Please select a different time, room, or lecturer.']);
        }

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
        $lecturers = User::where('role', 'lecturer')->with(['courseEnrollments.course', 'labs.course', 'conductedSessions'])->get();
        $availableCourses = Course::all();
        $availableLabs = Lab::with(['enrollments' => function($q) {
            $q->where('role', 'student');
        }])->get();

        return Inertia::render('Admin/ManageLecturers', [
            'lecturers' => $lecturers,
            'availableCourses' => $availableCourses,
            'availableLabs' => $availableLabs
        ]);
    }

    public function assignLecturer(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
        ]);

        if ($request->lab_id) {
            $lab = Lab::findOrFail($request->lab_id);
            $lab->update(['lecturer_id' => $request->user_id]);
        } else {
            CourseEnrollment::updateOrCreate(
                ['user_id' => $request->user_id, 'course_id' => $request->course_id, 'role' => 'lecturer'],
                ['lab_id' => null, 'enrolled_at' => now()]
            );
        }

        return back()->with('success', 'Lecturer assigned successfully.');
    }

    public function removeLecturerAssignment(Request $request, $id)
    {
        if ($request->type === 'lab') {
            Lab::findOrFail($id)->update(['lecturer_id' => null]);
        } else {
            CourseEnrollment::findOrFail($id)->delete();
        }
        return back()->with('success', 'Assignment removed.');
    }

    public function removeLecturerCourseAssignments(Request $request, $courseId)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        // Remove Open Class (CourseEnrollment)
        CourseEnrollment::where('user_id', $request->user_id)
            ->where('course_id', $courseId)
            ->where('role', 'lecturer')
            ->delete();

        // Remove from Labs
        Lab::where('lecturer_id', $request->user_id)
            ->where('course_id', $courseId)
            ->update(['lecturer_id' => null]);

        return back()->with('success', 'All assignments for this course removed.');
    }

    /**
     * 6. Student Management
     */
    public function manageStudents()
    {
        $students = User::where('role', 'student')->with(['courseEnrollments.course', 'courseEnrollments.lab', 'programme'])->get();
        return Inertia::render('Admin/ManageStudents', [
            'students' => $students,
            'availableCourses' => Course::all(),
            'availableProgrammes' => \App\Models\Programme::all(),
            'availableLabs' => Lab::with(['enrollments' => function($q) {
                $q->where('role', 'student');
            }])->get()
        ]);
    }

    public function assignStudent(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'lab_id' => 'nullable|exists:labs,id',
        ]);

        if ($request->lab_id) {
            $lab = Lab::findOrFail($request->lab_id);
            $currentEnrollments = CourseEnrollment::where('lab_id', $lab->id)
                ->where('role', 'student')
                ->count();
            
            // Note: Simplistic capacity check. In a real system you'd count existing users not already in this lab.
            if ($currentEnrollments + count($request->user_ids) > $lab->capacity) {
                return back()->withErrors(['lab_id' => 'Adding these students would exceed the lab maximum capacity.']);
            }
        }

        foreach ($request->user_ids as $userId) {
            CourseEnrollment::updateOrCreate(
                ['user_id' => $userId, 'course_id' => $request->course_id, 'role' => 'student'],
                ['lab_id' => $request->lab_id, 'enrolled_at' => now()]
            );
        }

        return back()->with('success', count($request->user_ids) . ' student(s) enrolled successfully.');
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

    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (!$lines || count($lines) <= 1) {
            return back()->withErrors(['file' => 'The uploaded file is empty or contains no data.']);
        }

        $rows = array_map('str_getcsv', $lines);
        $header = array_shift($rows); // Remove header

        $errors = [];
        $validatedData = [];
        $emailsInFile = [];
        $idsInFile = [];
        
        // Pre-fetch programs to avoid DB overhead
        $programmes = \App\Models\Programme::all()->pluck('id', 'code')->toArray();

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2; // +1 for 0-indexing, +1 for header row
            
            // 1. Basic Column Count check
            if (count($row) < 3) {
                $errors[] = "Line {$lineNumber}: Missing required columns. Expected at least Name, Email, and Student ID.";
                continue;
            }

            $data = [
                'name' => trim($row[0] ?? ''),
                'email' => trim($row[1] ?? ''),
                'student_id' => trim($row[2] ?? ''),
                'programme_code' => strtoupper(trim($row[3] ?? '')),
            ];

            // 2. Validate Row Data using Laravel's Validator
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'student_id' => 'required|string|max:255|unique:users,student_id',
            ], [
                'email.unique' => "The email ':input' is already registered in the system.",
                'student_id.unique' => "The Student ID ':input' is already registered in the system.",
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    $errors[] = "Line {$lineNumber}: {$msg}";
                }
            }

            // 3. Check for duplicates WITHIN the file
            if (in_array($data['email'], $emailsInFile)) {
                $errors[] = "Line {$lineNumber}: Duplicate email found within the file: " . $data['email'];
            }
            if (in_array($data['student_id'], $idsInFile)) {
                $errors[] = "Line {$lineNumber}: Duplicate Student ID found within the file: " . $data['student_id'];
            }

            $emailsInFile[] = $data['email'];
            $idsInFile[] = $data['student_id'];

            // 4. Resolve Programme ID
            if ($data['programme_code'] && !isset($programmes[$data['programme_code']])) {
                $errors[] = "Line {$lineNumber}: Invalid Programme Code: " . $data['programme_code'];
            }

            $validatedData[] = array_merge($data, [
                'programme_id' => $programmes[$data['programme_code']] ?? null
            ]);
        }

        // If any errors exist across the whole file, reject everything
        if (!empty($errors)) {
            // Limit errors to first 10 to avoid overwhelming the UI
            $displayErrors = array_slice($errors, 0, 10);
            if (count($errors) > 10) {
                $displayErrors[] = "...and " . (count($errors) - 10) . " more errors.";
            }
            return back()->withErrors(['csv' => $displayErrors]);
        }

        // Only if ALL rows are valid, we perform the import
        $importedCount = 0;
        DB::transaction(function () use ($validatedData, &$importedCount) {
            foreach ($validatedData as $user) {
                User::create([
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'username' => $user['student_id'],
                    'student_id' => $user['student_id'],
                    'programme_id' => $user['programme_id'],
                    'role' => 'student',
                    'password' => Hash::make($user['student_id']),
                ]);
                $importedCount++;
            }
        });

        return back()->with('success', "Successfully imported {$importedCount} student accounts!");
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
