<?php

use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebAttendanceController;
use App\Http\Controllers\WebLecturerDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebLecturerTimetableController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// TODO: kena setup permission nanti, ni semua xsetup lagi permissions sebab ni untuk auth so xperlu setup permissions
// AUTH ---
// Login Routes
// 1. The GET route: Displays the login form
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
// 2. The POST route: Handles the form submission
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');

// Forgot Password Route
Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');

// Reset Password Route
Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Logout Route
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// =====================================================================
// ADMIN ROUTES
// =====================================================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Lecturer Management
    Route::get('/lecturers', [AdminController::class, 'manageLecturers'])->name('lecturers.index');
    Route::post('/lecturers/assign', [AdminController::class, 'assignLecturer'])->name('lecturers.assign');
    Route::post('/lecturers/assignment/{id}', [AdminController::class, 'removeLecturerAssignment'])->name('lecturers.remove');

    // Student Management
    Route::get('/students', [AdminController::class, 'manageStudents'])->name('students.index');
    Route::post('/students/assign', [AdminController::class, 'assignStudent'])->name('students.assign');

    // Course & Lab Management (CRUD)
    Route::get('/courses', [AdminController::class, 'manageCourses'])->name('courses.index');
    Route::post('/courses', [AdminController::class, 'storeCourse'])->name('courses.store');
    Route::post('/courses/update/{id}', [AdminController::class, 'updateCourse'])->name('courses.update');
    Route::post('/labs', [AdminController::class, 'storeLab'])->name('labs.store');
    // User Management
    Route::get('/users', [AdminController::class, 'manageUsers'])->name('users.index');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/users/update/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.destroy');
    Route::post('/users/import', [AdminController::class, 'importStudents'])->name('users.import');

    // Class Session Management (Scheduling)
    Route::get('/sessions', [AdminController::class, 'manageSessions'])->name('sessions.index');
    Route::post('/sessions', [AdminController::class, 'storeSession'])->name('sessions.store');
    Route::post('/sessions/update/{id}', [AdminController::class, 'updateSession'])->name('sessions.update');
    Route::post('/sessions/{id}', [AdminController::class, 'deleteSession'])->name('sessions.destroy');

    // Analytics
    Route::get('/analytics', [App\Http\Controllers\WebAnalyticsController::class, 'globalAnalytics'])->name('analytics');

    // Core System Deletions (Soft Deletes)
    Route::post('/courses/delete/{id}', [AdminController::class, 'deleteCourse'])->name('courses.destroy');
    Route::post('/labs/delete/{id}', [AdminController::class, 'deleteLab'])->name('labs.destroy');

    Route::get('/settings', [App\Http\Controllers\AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [App\Http\Controllers\AdminSettingsController::class, 'update'])->name('settings.update');
});

    //TODO: select class -> class session -> qr dashboard
    //TODO: add checkrole lecturer

Route::middleware(['auth'])->group(function () {
    Route::get('lecturer/dashboard',[WebLecturerDashboardController::class,'lecturerDashboard'])->name('lecturer.dashboard');
    Route::post('lecturer/sessions/{session}/toggle-cancel', [WebLecturerDashboardController::class, 'toggleCancel'])->name('lecturer.sessions.toggle-cancel');
    Route::get('lecturer/timetable',[WebLecturerTimetableController::class,'lecturerTimetable'])->name('lecturer.timetable');
    Route::get('lecturer/analytics', [App\Http\Controllers\WebAnalyticsController::class, 'lecturerAnalytics'])->name('lecturer.analytics');
    // Attendance Management
    Route::get('lecturer/sessions/{id}/attendance', [WebAttendanceController::class, 'show'])->name('lecturer.sessions.attendance');
    Route::post('lecturer/sessions/{id}/toggle', [WebAttendanceController::class, 'toggleSession'])->name('lecturer.sessions.toggle');
    Route::post('lecturer/sessions/{id}/mark-manual', [WebAttendanceController::class, 'markManual'])->name('lecturer.sessions.mark-manual');
    Route::post('lecturer/sessions/{id}/mark-all-present', [WebAttendanceController::class, 'markAllPresent'])->name('lecturer.sessions.mark-all-present');
    Route::post('lecturer/sessions/{id}/clear-all', [WebAttendanceController::class, 'clearAll'])->name('lecturer.sessions.clear-all');
    Route::get('lecturer/sessions/{id}/export', [WebAttendanceController::class, 'exportExcel'])->name('lecturer.sessions.export');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});

