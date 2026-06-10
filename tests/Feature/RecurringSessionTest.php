<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Lab;
use App\Models\Room;
use App\Models\ClassSession;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use function Pest\Laravel\{actingAs, post};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->course = Course::create(['code' => 'TEST101', 'name' => 'Test Course', 'faculty' => 'IT']);
    $this->lecturer = User::factory()->create(['role' => 'lecturer']);
    $this->lab = Lab::create(['course_id' => $this->course->id, 'name' => 'L01', 'capacity' => 30, 'lecturer_id' => $this->lecturer->id]);
    $this->room = Room::create(['name' => 'Room 1', 'capacity' => 30, 'location' => 'Block A']);
    
    // Set semester end date to 3 weeks from now
    SystemSetting::updateOrCreate(['key' => 'semester_end_date'], ['value' => '2026-07-01']);
});

test('admin can create recurring sessions', function () {
    $this->withoutMiddleware();
    $startTime = '2026-06-11 10:00:00';
    $endTime = '2026-06-11 12:00:00';

    $initialCount = ClassSession::count();
    $response = actingAs($this->admin)
        ->post(route('admin.sessions.store'), [
            'course_id' => $this->course->id,
            'lab_id' => $this->lab->id,
            'lecturer_id' => $this->lecturer->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'mode' => 'physical',
            'checkin_method' => 'qr',
            'is_recurring' => true,
        ]);

    $response->assertSessionHas('success');
    
    $finalCount = ClassSession::count();
    expect($finalCount - $initialCount)->toBe(3);
});

test('recurring sessions skip conflicts', function () {
    $this->withoutMiddleware();
    $startTime = '2026-06-11 10:00:00';
    $endTime = '2026-06-11 12:00:00';

    // Create a conflict for the second week (June 18)
    ClassSession::create([
        'course_id' => $this->course->id,
        'lab_id' => $this->lab->id,
        'lecturer_id' => $this->lecturer->id,
        'room_id' => $this->room->id,
        'start_time' => '2026-06-18 10:30:00',
        'end_time' => '2026-06-18 11:30:00',
        'mode' => 'physical',
        'checkin_method' => 'qr',
    ]);

    $initialCount = ClassSession::count();
    $response = actingAs($this->admin)
        ->post(route('admin.sessions.store'), [
            'course_id' => $this->course->id,
            'lab_id' => $this->lab->id,
            'lecturer_id' => $this->lecturer->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'mode' => 'physical',
            'checkin_method' => 'qr',
            'is_recurring' => true,
        ]);

    $response->assertSessionHas('success');

    // Should create June 11 and June 25, skip June 18
    $finalCount = ClassSession::count();
    expect($finalCount - $initialCount)->toBe(2); // 2 newly created (June 11, June 25)
});
