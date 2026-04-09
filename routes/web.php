<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WebAuthController;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');

// 2. The POST route: Handles the form submission
Route::post('/login', [WebAuthController::class, 'login']);
