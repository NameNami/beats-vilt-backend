<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Lab;
use App\Models\AttendanceRecord;
use App\Models\GamificationProfile;
use App\Models\Level;
use App\Models\Badge;
use App\Models\QrToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Levels
        Level::create(['level' => '1', 'xp_required' => 0]);
        Level::create(['level' => '2', 'xp_required' => 100]);
        
        // Setup Badges
        Badge::create([
            'name' => 'First Steps',
            'description' => 'Earn your first 100 XP',
            'type' => 'xp',
            'requirement_type' => 'total_xp',
            'requirement_value' => 100,
        ]);
    }

    protected function createTestSession($lecturer)
    {
        $course = Course::create(['code' => 'CS101', 'name' => 'Test Course', 'faculty' => 'FOC']);
        $course->lecturers()->attach($lecturer->id);
        
        return ClassSession::create([
            'course_id' => $course->id,
            'lecturer_id' => $lecturer->id,
            'start_time' => now()->subMinutes(5),
            'end_time' => now()->addHours(2),
            'mode' => 'physical',
            'checkin_method' => 'qr',
            'is_display' => true,
        ]);
    }

    public function test_student_earns_xp_on_qr_checkin()
    {
        $lecturer = User::factory()->create(['role' => 'lecturer']);
        $student = User::factory()->create(['role' => 'student']);
        $session = $this->createTestSession($lecturer);
        $token = QrToken::create([
            'session_id' => $session->id,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($student, 'sanctum')->postJson(route('api.student.check-in-qr'), [
            'class_session_id' => $session->id,
            'token' => $token->token,
            'timestamp' => (string) now()->timestamp,
        ]);

        $response->assertStatus(200);
        
        $profile = GamificationProfile::where('user_id', $student->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(100, $profile->total_xp); // 'present' status awards 100 XP
        $this->assertEquals(1, $profile->current_streak);
        
        // Check level up (100 XP is required for Level 2)
        $this->assertEquals(2, $profile->refresh()->level->level);
        
        // Check badge awarding
        $this->assertTrue($student->badges()->where('name', 'First Steps')->exists());
    }

    public function test_streak_increments_on_consecutive_days()
    {
        $lecturer = User::factory()->create(['role' => 'lecturer']);
        $student = User::factory()->create(['role' => 'student']);
        $session = $this->createTestSession($lecturer);
        
        // Fix "now" for this test
        $today = \Carbon\Carbon::create(2026, 5, 20, 10, 0, 0);
        \Carbon\Carbon::setTestNow($today);

        $profile = GamificationProfile::create([
            'user_id' => $student->id,
            'total_xp' => 0,
            'total_points' => 0,
            'current_streak' => 1,
        ]);

        // Create a record for yesterday
        AttendanceRecord::create([
            'user_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'present',
            'check_in_time' => $today->copy()->subDay()->startOfDay()->addHours(10),
            'checkin_method' => 'manual',
        ]);

        $service = app(\App\Services\GamificationService::class);
        $service->awardAttendanceRewards($student, 'present');

        $this->assertEquals(2, $profile->refresh()->current_streak);
        
        \Carbon\Carbon::setTestNow(); // Reset
    }

    public function test_streak_resets_on_missed_days()
    {
        $lecturer = User::factory()->create(['role' => 'lecturer']);
        $student = User::factory()->create(['role' => 'student']);
        $session = $this->createTestSession($lecturer);
        
        // Fix "now" for this test
        $today = \Carbon\Carbon::create(2026, 5, 20, 10, 0, 0);
        \Carbon\Carbon::setTestNow($today);

        $profile = GamificationProfile::create([
            'user_id' => $student->id,
            'total_xp' => 0,
            'total_points' => 0,
            'current_streak' => 5,
        ]);

        // Last attendance was 3 days ago
        AttendanceRecord::create([
            'user_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'present',
            'check_in_time' => $today->copy()->subDays(3)->startOfDay()->addHours(10),
            'checkin_method' => 'manual',
        ]);

        $service = app(\App\Services\GamificationService::class);
        $service->awardAttendanceRewards($student, 'present');

        $this->assertEquals(1, $profile->refresh()->current_streak);
        
        \Carbon\Carbon::setTestNow(); // Reset
    }
}
