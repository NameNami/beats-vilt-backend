<?php

use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebAttendanceController;
use App\Http\Controllers\WebLecturerDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebLecturerTimetableController;
use App\Http\Controllers\WebLecturerLeave;
use App\Http\Middleware\CheckRoleWeb;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

// TODO: kena setup permission nanti, ni semua xsetup lagi permissions sebab ni untuk auth so xperlu setup permissions
// AUTH ---
// Login Routes
// 1. The GET route: Displays the login form
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
// 2. The POST route: Handles the form submission
Route::post('/login', [WebAuthController::class, 'login']);

// Forgot Password Route
Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');

// Reset Password Route
Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

//Logout
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
// --- AUTH

// TODO: select class -> class session -> qr dashboard
Route::middleware(['auth', CheckRoleWeb::class . ':lecturer'])->group(function () {
    Route::get('lecturer/dashboard',[WebLecturerDashboardController::class,'lecturerDashboard'])->name('lecturer.dashboard');
    Route::post('lecturer/sessions/{session}/toggle-cancel', [WebLecturerDashboardController::class, 'toggleCancel'])->name('lecturer.sessions.toggle-cancel');
    Route::get('lecturer/timetable',[WebLecturerTimetableController::class,'lecturerTimetable'])->name('lecturer.timetable');
    Route::get('lecturer/leave', [WebLecturerLeave::class, 'index'])->name('lecturer.leave');
    Route::post('lecturer/leave/{application}/status', [WebLecturerLeave::class, 'updateStatus'])->name('lecturer.leave.status');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});

