<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebLecturerTimetableController extends Controller
{
    public function lecturerTimetable()
    {
        $lecturerId = Auth::id();
        return Inertia::render('LecturerTimetable');
    }
}
