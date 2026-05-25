<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassSession;
use App\Models\Lab;
use App\Models\Course;
use App\Models\Room;
use Illuminate\Support\Carbon;

class ClassSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::with('labs')->get();
        $labs = Lab::all();
        $rooms = Room::all();

        if ($rooms->isEmpty() || $courses->isEmpty()) {
            return; // prerequisites not met
        }

        $roomCount = $rooms->count();
        $now = Carbon::now();
        $sessionIndex = 0;

        // Generate sessions for the last 6 weeks and next 2 weeks
        for ($weekOffset = -6; $weekOffset <= 2; $weekOffset++) {

            // 1. Generate LECTURES for each course (1 per week)
            foreach ($courses as $course) {
                // Lectures are usually on a fixed day, let's say Monday
                $baseDate = $now->copy()->startOfWeek()->addWeeks($weekOffset);

                $isPast = $baseDate->lt($now->copy()->startOfDay());
                $isToday = $baseDate->isToday();

                $start = $baseDate->copy()->setTime(10, 0); // 10:00 AM
                $end = $baseDate->copy()->setTime(12, 0);   // 12:00 PM

                $is_completed = $isPast || ($isToday && $now->gt($end));
                $is_active = $isToday && $now->between($start, $end);

                // Use the lecturer from the first lab or a default
                $lecturerId = $course->labs->first()?->lecturer_id ?? 1;

                ClassSession::create([
                    'course_id'        => $course->id,
                    'lab_id'           => null, // This signifies a Lecture
                    'lecturer_id'      => $lecturerId,
                    'room_id'          => $rooms[$sessionIndex % $roomCount]->id,
                    'start_time'       => $start,
                    'end_time'         => $end,
                    'mode'             => 'physical',
                    'checkin_method'   => 'qr', // Lectures often use QR
                    'is_display'       => $is_active,
                    'is_cancelled'     => false,
                    'is_completed'     => $is_completed,
                    'announce_cancelled' => false,
                ]);
                $sessionIndex++;
            }

            // 2. Generate LAB SESSIONS for each lab
            foreach ($labs as $lab) {
                // Determine the day of the week based on lab ID to distribute classes
                // Skip Monday (reserved for lectures above)
                $dayOffset = ($lab->id % 4) + 1; // Tuesday to Friday
                $baseDate = $now->copy()->startOfWeek()->addWeeks($weekOffset)->addDays($dayOffset);

                $isPast = $baseDate->lt($now->copy()->startOfDay());
                $isToday = $baseDate->isToday();

                // Create 2 sessions per lab per week at different times
                $times = [
                    ['start' => '09:00', 'end' => '11:00'],
                    ['start' => '14:00', 'end' => '16:00'],
                ];

                foreach ($times as $time) {
                    $start = $baseDate->copy()->setTimeFromTimeString($time['start']);
                    $end = $baseDate->copy()->setTimeFromTimeString($time['end']);

                    $is_completed = $isPast || ($isToday && $now->gt($end));
                    $is_active = $isToday && $now->between($start, $end);

                    ClassSession::create([
                        'course_id'        => $lab->course_id,
                        'lab_id'           => $lab->id,
                        'lecturer_id'      => $lab->lecturer_id,
                        'room_id'          => $rooms[$sessionIndex % $roomCount]->id,
                        'start_time'       => $start,
                        'end_time'         => $end,
                        'mode'             => 'physical',
                        'checkin_method'   => 'ble',
                        'is_display'       => $is_active,
                        'is_cancelled'     => false,
                        'is_completed'     => $is_completed,
                        'announce_cancelled' => false,
                    ]);
                    $sessionIndex++;
                }
            }
        }
    }
}
