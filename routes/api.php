<?php

use App\Http\Controllers\Api\StudentAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BeaconHeartbeatController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\StudentDataController;
use function Pest\Laravel\post;
use App\Http\Middleware\CheckRoleWeb;
use App\Http\Middleware\CheckRoleApi;

// Student Auth (Login & Forgot Password)
Route::post('/student/login', [StudentAuthController::class, 'login'])->name('api.student.login');
Route::post('/student/forgot-password', [StudentAuthController::class, 'sendResetLinkEmail']);

// TODO: nanti sekali kan yg permission sama | CheckRoleWeb yg sama
Route::post('/logout', [StudentAuthController::class, 'logout'])
    ->name('api.logout')
    ->middleware([
        'auth:sanctum', // 1. Front door: Are they logged in?
        CheckRoleApi::class.':student' // 2. VIP door: Are they a teacher?
    ]);

// Beacon
Route::post('/beacon/heartbeat', [BeaconHeartbeatController::class, 'heartbeat']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Check-in
Route::post('/student/check-in-ble', [AttendanceController::class, 'checkInBle'])
    ->name('api.student.check-in-ble')
    ->middleware([
        'auth:sanctum', // Are they logged in?
        CheckRoleApi::class.':student' // Are they a student?
    ]);

Route::post('/student/check-in-qr', [AttendanceController::class, 'checkInQr'])
    ->name('api.student.check-in-qr')
    ->middleware([
        'auth:sanctum', // Are they logged in?
        CheckRoleApi::class.':student' // Are they a student?
    ]);

// Student Data
Route::middleware(['auth:sanctum', CheckRoleApi::class.':student'])->group(function () {
    Route::get('/student/profile', [StudentDataController::class, 'getProfile'])->name('api.student.profile');
    Route::get('/student/courses', [StudentDataController::class, 'getCourses'])->name('api.student.courses');
    Route::get('/student/schedule', [StudentDataController::class, 'getSchedule'])->name('api.student.schedule');
    Route::get('/student/attendance', [StudentDataController::class, 'getAttendanceHistory'])->name('api.student.attendance');
    Route::get('/student/notifications', [StudentDataController::class, 'getNotifications'])->name('api.student.notifications');
    Route::get('/student/leaderboard', [StudentDataController::class, 'getLeaderboard'])->name('api.student.leaderboard');
    Route::get('/student/rewards', [StudentDataController::class, 'getRewards'])->name('api.student.rewards');
    Route::post('/student/rewards/redeem', [StudentDataController::class, 'redeemReward'])->name('api.student.rewards.redeem');
    Route::get('/student/leaves', [StudentDataController::class, 'getLeaveApplications'])->name('api.student.leaves');


    Route::post('/student/leaves', [StudentDataController::class, 'submitLeave'])->name('api.student.leaves.submit');
});



