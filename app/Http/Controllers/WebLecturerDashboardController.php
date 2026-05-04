<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WebLecturerDashboardController extends Controller
{
    public function lecturerDashboard()
    {
        return inertia::render('LecturerDashboard');
    }
}
