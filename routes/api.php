<?php

use App\Http\Controllers\Api\StudentAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use app\Http\Controllers\Api\BeaconHeartbeatController;
use function Pest\Laravel\post;

// Student Auth (Login & Forgot Password)
Route::post('/student/login', [StudentAuthController::class, 'login'])->name('api.student.login');
Route::post('/student/forgot-password', [StudentAuthController::class, 'sendResetLinkEmail']);

// Beacon
Route::post('/beacon/heartbeat', [BeaconHeartbeatController::class, 'heartbeat']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
