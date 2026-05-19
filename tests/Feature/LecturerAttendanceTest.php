<?php

use App\Models\User;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Lab;
use App\Models\Room;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTestSession($lecturer, $courseCode = 'CS101') {
    $course = Course::create(['code' => $courseCode, 'name' => 'Test Course', 'faculty' => 'FOC']);
    $course->lecturers()->attach($lecturer->id); // Ensure lecturer is enrolled
    $lab = Lab::create(['name' => 'L01', 'capacity' => 30, 'course_id' => $course->id]);
    
    return ClassSession::create([
        'course_id' => $course->id,
        'lecturer_id' => $lecturer->id,
        'start_time' => now(),
        'end_time' => now()->addHours(2),
        'mode' => 'physical',
        'checkin_method' => 'qr',
        'lab_id' => $lab->id,
    ]);
}

test('lecturer can view session details and student list', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);
    $session = createTestSession($lecturer);
    $student = User::factory()->create(['role' => 'student', 'student_id' => 'S12345']);
    $session->course->students()->attach($student->id);

    $response = $this->actingAs($lecturer)->get(route('lecturer.sessions.show', $session->id));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'session' => ['id', 'course_name', 'course_code', 'mode', 'location', 'start_time', 'end_time', 'is_display', 'qr_token'],
        'students' => [['id', 'student_id', 'name', 'status']],
        'stats' => ['present', 'late', 'absent', 'leave', 'total']
    ]);
});

test('lecturer can toggle session display status', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);
    $session = createTestSession($lecturer, 'CS102');
    $session->update(['is_display' => false]);

    $response = $this->actingAs($lecturer, 'web')->postJson(route('lecturer.sessions.toggle-display', $session->id), [
        'is_display' => true
    ]);

    $response->assertStatus(200);
    expect($session->refresh()->is_display)->toBeTrue();
});

test('lecturer can mark manual attendance for a student', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);
    $student = User::factory()->create(['role' => 'student']);
    $session = createTestSession($lecturer, 'CS103');

    $response = $this->actingAs($lecturer)->postJson(route('lecturer.sessions.mark-attendance', $session->id), [
        'user_id' => $student->id,
        'status' => 'present'
    ]);

    $response->assertStatus(200);
    expect(AttendanceRecord::where('session_id', $session->id)->where('user_id', $student->id)->first()->status)->toBe('present');
});

test('lecturer can mark all students as present', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);
    $session = createTestSession($lecturer, 'CS104');
    $students = User::factory()->count(3)->create(['role' => 'student']);
    $session->course->students()->attach($students->pluck('id'));

    $response = $this->actingAs($lecturer)->postJson(route('lecturer.sessions.mark-all-present', $session->id));

    $response->assertStatus(200);
    expect(AttendanceRecord::where('session_id', $session->id)->count())->toBe(3);
});

test('lecturer can reset attendance for a session', function () {
    $lecturer = User::factory()->create(['role' => 'lecturer']);
    $session = createTestSession($lecturer, 'CS105');
    AttendanceRecord::create([
        'user_id' => User::factory()->create()->id,
        'session_id' => $session->id,
        'status' => 'present',
        'check_in_time' => now(),
        'checkin_method' => 'qr'
    ]);

    $response = $this->actingAs($lecturer)->postJson(route('lecturer.sessions.reset-attendance', $session->id));

    $response->assertStatus(200);
    expect(AttendanceRecord::where('session_id', $session->id)->count())->toBe(0);
});
