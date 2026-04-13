<?php

use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WebAuthController;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');

// 2. The POST route: Handles the form submission
Route::post('/login', [WebAuthController::class, 'login']);

Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');

Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
