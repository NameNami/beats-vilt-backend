<?php

use App\Http\Controllers\Api\StudentAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Middleware\CheckRole;

Route::post('/student/login', [StudentAuthController::class, 'login'])->name('api.student.login');
Route::post('/student/forgot-password', [StudentAuthController::class, 'sendResetLinkEmail']);

Route::post('/logout', [StudentAuthController::class, 'logout'])
    ->name('api.logout')
    ->middleware([
        'auth:sanctum', // 1. Front door: Are they logged in?
        CheckRole::class.':student' // 2. VIP door: Are they a teacher?
    ]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
