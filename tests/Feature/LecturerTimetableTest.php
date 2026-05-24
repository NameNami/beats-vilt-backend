<?php

use App\Models\User;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Lab;
use App\Models\Room;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('lecturer can see sessions for current week', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);
    
    SystemSetting::create([
        'key' => 'semester_start_date',
        'value' => '2026-03-02', // Monday
        'description' => 'Semester start date'
    ]);
    
    SystemSetting::create([
        'key' => 'semester_total_weeks',
        'value' => '14',
        'description' => 'Semester total weeks'
    ]);

    $course = Course::create([
        'code' => 'CS101', 
        'name' => 'Intro to Computer Science',
        'faculty' => 'FOC'
    ]);
    
    $lab = Lab::create([
        'name' => 'L01',
        'capacity' => 30,
        'course_id' => $course->id,
        'lecturer_id' => $lecturer->id
    ]);

    // Session in current week (2026-05-18 is a Monday)
    $now = Carbon::parse('2026-05-18 10:00:00');
    Carbon::setTestNow($now);
    
    $thisWeekSession = ClassSession::create([
        'course_id' => $course->id,
        'lab_id' => $lab->id,
        'lecturer_id' => $lecturer->id,
        'start_time' => $now,
        'end_time' => $now->copy()->addHours(2),
        'mode' => 'physical',
        'checkin_method' => 'qr',
    ]);
    
    $nextWeekSession = ClassSession::create([
        'course_id' => $course->id,
        'lab_id' => $lab->id,
        'lecturer_id' => $lecturer->id,
        'start_time' => $now->copy()->addDays(7),
        'end_time' => $now->copy()->addDays(7)->addHours(2),
        'mode' => 'physical',
        'checkin_method' => 'qr',
    ]);

    $response = $this->actingAs($lecturer)->get(route('lecturer.timetable'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('LecturerTimetable')
        ->has('sessions', 1)
        ->where('sessions.0.id', $thisWeekSession->id)
        ->where('weekStartDate', '2026-05-18')
        ->where('currentWeek', 12)
        ->where('semesterStart', '2026-03-02')
        ->where('semesterEnd', '2026-06-14')
    );
});

test('lecturer can see sessions for a specific week', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);

    SystemSetting::create([
        'key' => 'semester_start_date',
        'value' => '2026-03-02', // Monday
        'description' => 'Semester start date'
    ]);
    
    SystemSetting::create([
        'key' => 'semester_total_weeks',
        'value' => '14',
        'description' => 'Semester total weeks'
    ]);

    $course = Course::create([
        'code' => 'CS101', 
        'name' => 'Intro to Computer Science',
        'faculty' => 'FOC'
    ]);
    
    $lab = Lab::create([
        'name' => 'L01',
        'capacity' => 30,
        'course_id' => $course->id,
        'lecturer_id' => $lecturer->id
    ]);

    $now = Carbon::parse('2026-05-18 10:00:00');
    Carbon::setTestNow($now);
    
    $thisWeekSession = ClassSession::create([
        'course_id' => $course->id,
        'lab_id' => $lab->id,
        'lecturer_id' => $lecturer->id,
        'start_time' => $now,
        'end_time' => $now->copy()->addHours(2),
        'mode' => 'physical',
        'checkin_method' => 'qr',
    ]);
    
    $nextWeekSession = ClassSession::create([
        'course_id' => $course->id,
        'lab_id' => $lab->id,
        'lecturer_id' => $lecturer->id,
        'start_time' => $now->copy()->addDays(7),
        'end_time' => $now->copy()->addDays(7)->addHours(2),
        'mode' => 'physical',
        'checkin_method' => 'qr',
    ]);

    $response = $this->actingAs($lecturer)->get(route('lecturer.timetable', ['date' => '2026-05-25']));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('LecturerTimetable')
        ->has('sessions', 1)
        ->where('sessions.0.id', $nextWeekSession->id)
        ->where('weekStartDate', '2026-05-25')
        ->where('currentWeek', 13)
        ->where('semesterStart', '2026-03-02')
        ->where('semesterEnd', '2026-06-14')
    );
});
