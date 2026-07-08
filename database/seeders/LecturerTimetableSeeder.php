<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Lab;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class LecturerTimetableSeeder extends Seeder
{
    /**
     * OPTIONAL/DEMO SEEDER
     * --------------------
     * This seeder creates a simple per-lecturer timetable for a single week,
     * aligned to the semester_start_date's Monday. It was originally used to
     * quickly populate UI screens. However, the main ClassSessionSeeder already
     * generates a full-semester schedule for courses and labs.
     *
     * To avoid duplicate/overlapping sessions, this seeder is no longer called
     * from DatabaseSeeder by default. You may still run it manually when needed:
     *
     *   php artisan db:seed --class=Database\\Seeders\\LecturerTimetableSeeder
     *
     * Run the database seeds.
     */
    public function run(): void
    {
        $lecturers = User::where('role', 'lecturer')->get();
        $rooms = Room::all();
        $courses = Course::all();
        $labs = Lab::all();

        if ($lecturers->isEmpty() || $rooms->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('Ensure you have seeded users (lecturers), rooms, and courses first.');
            return;
        }

        $semStartStr = SystemSetting::get('semester_start_date', '2026-07-01');
        $startOfWeek = Carbon::parse($semStartStr)->startOfWeek(Carbon::MONDAY);

        foreach ($lecturers as $lecturer) {
            // Assign 2 lectures and 1 lab per week for each lecturer for the current week
            $course = $courses->random();
            $room = $rooms->random();

            // Create a physical lecture on Monday
            ClassSession::create([
                'course_id' => $course->id,
                'lab_id' => null,
                'lecturer_id' => $lecturer->id,
                'room_id' => $room->id,
                'start_time' => $startOfWeek->copy()->addHours(8), // Monday 8:00 AM
                'end_time' => $startOfWeek->copy()->addHours(10),  // Monday 10:00 AM
                'mode' => 'physical',
                'checkin_method' => 'qr',
                'is_display' => true,
                'is_cancelled' => false,
                'is_completed' => false,
                'announce_cancelled' => false,
            ]);

            // Create an online lecture on Wednesday
            ClassSession::create([
                'course_id' => $course->id,
                'lab_id' => null,
                'lecturer_id' => $lecturer->id,
                'room_id' => null,
                'start_time' => $startOfWeek->copy()->addDays(2)->addHours(10), // Wednesday 10:00 AM
                'end_time' => $startOfWeek->copy()->addDays(2)->addHours(12),   // Wednesday 12:00 PM
                'mode' => 'online',
                'checkin_method' => 'qr',
                'is_display' => true,
                'is_cancelled' => false,
                'is_completed' => false,
                'announce_cancelled' => false,
            ]);

            if ($labs->isNotEmpty()) {
                $lab = $labs->random();
                // Create a lab session on Thursday
                ClassSession::create([
                    'course_id' => $lab->course_id,
                    'lab_id' => $lab->id,
                    'lecturer_id' => $lecturer->id,
                    'room_id' => $rooms->random()->id,
                    'start_time' => $startOfWeek->copy()->addDays(3)->addHours(14), // Thursday 2:00 PM
                    'end_time' => $startOfWeek->copy()->addDays(3)->addHours(16),   // Thursday 4:00 PM
                    'mode' => 'physical',
                    'checkin_method' => 'ble',
                    'is_display' => true,
                    'is_cancelled' => false,
                    'is_completed' => false,
                    'announce_cancelled' => false,
                ]);
            }

            // Optionally create a cancelled class on Friday for UI testing
            ClassSession::create([
                'course_id' => $course->id,
                'lab_id' => null,
                'lecturer_id' => $lecturer->id,
                'room_id' => $rooms->random()->id,
                'start_time' => $startOfWeek->copy()->addDays(4)->addHours(9), // Friday 9:00 AM
                'end_time' => $startOfWeek->copy()->addDays(4)->addHours(11),  // Friday 11:00 AM
                'mode' => 'physical',
                'checkin_method' => 'qr',
                'is_display' => true,
                'is_cancelled' => true,
                'is_completed' => false,
                'announce_cancelled' => true,
            ]);
        }
    }
}
