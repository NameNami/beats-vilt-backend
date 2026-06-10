<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminBleDeviceController;
use App\Http\Controllers\AdminBroadcastController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminGamificationController;
use App\Http\Controllers\AdminLeaveController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminSystemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WebAnalyticsController;
use App\Http\Controllers\WebAttendanceController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebLecturerDashboardController;
use App\Http\Controllers\WebLecturerLeave;
use App\Http\Controllers\WebLecturerReport;
use App\Http\Controllers\WebLecturerSettingsController;
use App\Http\Controllers\WebLecturerTimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

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
    Route::post('/lecturers/assignment/course/{courseId}', [AdminController::class, 'removeLecturerCourseAssignments'])->name('lecturers.remove.course');

    // Student Management
    Route::get('/students', [AdminController::class, 'manageStudents'])->name('students.index');
    Route::post('/students/assign', [AdminController::class, 'assignStudent'])->name('students.assign');

    // Course & Lab Management (CRUD)
    Route::get('/courses', [AdminController::class, 'manageCourses'])->name('courses.index');
    Route::post('/courses', [AdminController::class, 'storeCourse'])->name('courses.store');
    Route::post('/courses/update/{id}', [AdminController::class, 'updateCourse'])->name('courses.update');
    Route::post('/labs', [AdminController::class, 'storeLab'])->name('labs.store');
    Route::post('/labs/update/{id}', [AdminController::class, 'updateLab'])->name('labs.update');

    // User Management
    Route::get('/users', [AdminController::class, 'manageUsers'])->name('users.index');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/users/import', [AdminController::class, 'importStudents'])->name('users.import');
    Route::post('/users/update/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.destroy');

    // Broadcast Announcements
    Route::get('/broadcasts', [AdminBroadcastController::class, 'index'])->name('broadcasts.index');
    Route::post('/broadcasts', [AdminBroadcastController::class, 'store'])->name('broadcasts.store');

    // Class Session Management (Scheduling)
    Route::get('/sessions', [AdminController::class, 'manageSessions'])->name('sessions.index');
    Route::post('/sessions', [AdminController::class, 'storeSession'])->name('sessions.store');
    Route::post('/sessions/update/{id}', [AdminController::class, 'updateSession'])->name('sessions.update');
    Route::post('/sessions/{id}', [AdminController::class, 'deleteSession'])->name('sessions.destroy');

    // Global Leave Management
    Route::get('/leave-management', [AdminLeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave-management', [AdminLeaveController::class, 'store'])->name('leave.store');

    // Analytics & Audit
    Route::get('/analytics', [WebAnalyticsController::class, 'globalAnalytics'])->name('analytics');
    Route::get('/analytics/export', [WebAnalyticsController::class, 'exportGlobalAnalytics'])->name('analytics.export');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit.logs');

    // System Health
    Route::get('/system-health', [AdminSystemController::class, 'health'])->name('system.health');
    Route::get('/system-health/backup', [AdminSystemController::class, 'downloadBackup'])->name('system.backup');
    Route::post('/system-health/rollover', [AdminSystemController::class, 'rolloverSemester'])->name('system.rollover');

    // Core System Deletions (Soft Deletes)
    Route::post('/courses/delete/{id}', [AdminController::class, 'deleteCourse'])->name('courses.destroy');
    Route::post('/labs/delete/{id}', [AdminController::class, 'deleteLab'])->name('labs.destroy');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/profile', [AdminSettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [AdminSettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/photo', [AdminSettingsController::class, 'updatePhoto'])->name('settings.photo');
    Route::delete('/settings/photo', [AdminSettingsController::class, 'deletePhoto'])->name('settings.photo.delete');

    // BLE Device Management
    Route::get('/ble-devices', [AdminBleDeviceController::class, 'index'])->name('ble-devices.index');
    Route::put('/ble-devices/{beacon}', [AdminBleDeviceController::class, 'update'])->name('ble-devices.update');
    Route::put('/ble-devices/{beacon}/unassign', [AdminBleDeviceController::class, 'unassign'])->name('ble-devices.unassign');
    Route::post('/ble-devices/scan', [AdminBleDeviceController::class, 'scan'])->name('ble-devices.scan');

    // Gamification Management
    Route::get('/badges', [AdminGamificationController::class, 'manageBadges'])->name('badges.index');
    Route::post('/badges', [AdminGamificationController::class, 'storeBadge'])->name('badges.store');
    Route::post('/badges/update/{id}', [AdminGamificationController::class, 'updateBadge'])->name('badges.update');
    Route::post('/badges/delete/{id}', [AdminGamificationController::class, 'deleteBadge'])->name('badges.destroy');

    Route::get('/redemptions', [AdminGamificationController::class, 'manageRedemptions'])->name('redemptions.index');
    Route::post('/rewards', [AdminGamificationController::class, 'storeReward'])->name('rewards.store');
    Route::post('/rewards/update/{id}', [AdminGamificationController::class, 'updateReward'])->name('rewards.update');
    Route::post('/rewards/delete/{id}', [AdminGamificationController::class, 'deleteReward'])->name('rewards.destroy');
    Route::post('/redemptions/status/{id}', [AdminGamificationController::class, 'updateRedemptionStatus'])->name('redemptions.update-status');
});

Route::middleware(['auth', 'role:lecturer,admin'])->group(function () {
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});

Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('lecturer/dashboard', [WebLecturerDashboardController::class, 'lecturerDashboard'])->name('lecturer.dashboard');
    Route::post('lecturer/sessions/{session}/toggle-cancel', [WebLecturerDashboardController::class, 'toggleCancel'])->name('lecturer.sessions.toggle-cancel');
    Route::get('lecturer/timetable', [WebLecturerTimetableController::class, 'lecturerTimetable'])->name('lecturer.timetable');
    Route::get('lecturer/leave', [WebLecturerLeave::class, 'index'])->name('lecturer.leave');
    Route::post('lecturer/leave/{application}/status', [WebLecturerLeave::class, 'updateStatus'])->name('lecturer.leave.status');
    Route::get('lecturer/attendance', [WebAttendanceController::class, 'index'])->name('lecturer.attendance');
    Route::get('lecturer/sessions/{session}', [WebAttendanceController::class, 'show'])->name('lecturer.sessions.show');
    Route::post('lecturer/sessions/{session}/generate-qr', [WebAttendanceController::class, 'generateQr'])->name('lecturer.sessions.generate-qr');
    Route::post('lecturer/sessions/{session}/toggle-display', [WebAttendanceController::class, 'toggleDisplay'])->name('lecturer.sessions.toggle-display');
    Route::post('lecturer/sessions/{session}/mark-attendance', [WebAttendanceController::class, 'markAttendance'])->name('lecturer.sessions.mark-attendance');
    Route::post('lecturer/sessions/{session}/mark-all-present', [WebAttendanceController::class, 'markAllPresent'])->name('lecturer.sessions.mark-all-present');
    Route::post('lecturer/sessions/{session}/reset-attendance', [WebAttendanceController::class, 'resetAttendance'])->name('lecturer.sessions.reset-attendance');

    Route::get('lecturer/reports', [WebLecturerReport::class, 'index'])->name('lecturer.reports');
    Route::get('lecturer/reports/export', [WebLecturerReport::class, 'exportCsv'])->name('lecturer.reports.export');

    Route::get('lecturer/settings', [WebLecturerSettingsController::class, 'settings'])->name('lecturer.settings');
    Route::post('lecturer/settings/profile', [WebLecturerSettingsController::class, 'updateProfile'])->name('lecturer.settings.profile');
    Route::post('lecturer/settings/password', [WebLecturerSettingsController::class, 'updatePassword'])->name('lecturer.settings.password');
    Route::post('lecturer/settings/photo', [WebLecturerSettingsController::class, 'updatePhoto'])->name('lecturer.settings.photo');
    Route::delete('lecturer/settings/photo', [WebLecturerSettingsController::class, 'deletePhoto'])->name('lecturer.settings.photo.delete');
});
