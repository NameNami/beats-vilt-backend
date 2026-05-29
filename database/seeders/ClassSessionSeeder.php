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
            return;
        }

        $now = Carbon::now();
        $lecturerSchedules = [];

        // Generate sessions for the last 6 weeks and next 2 weeks
        for ($weekOffset = -6; $weekOffset <= 2; $weekOffset++) {
            $weekStart = $now->copy()->startOfWeek()->addWeeks($weekOffset);

            // 1. Generate LECTURES for each course
            foreach ($courses as $course) {
                $lecturerId = $course->labs->first()?->lecturer_id ?? 1;
                
                // Try to find a slot on Monday (Day 0)
                $slot = $this->findFreeSlot($lecturerSchedules, $lecturerId, $weekStart, 0);
                
                if ($slot) {
                    // Lectures: ~70% chance of being online
                    $mode = (rand(1, 100) <= 70) ? 'online' : 'physical';
                    $this->createSession($course->id, null, $lecturerId, $rooms, $slot['start'], $slot['end'], 'qr', $mode, $now);
                    $this->markOccupied($lecturerSchedules, $lecturerId, $slot['start'], $slot['end']);
                }
            }

            // 2. Generate LAB SESSIONS for each lab
            foreach ($labs as $lab) {
                // Each lab has 2 sessions per week
                for ($i = 0; $i < 2; $i++) {
                    // Try to find a slot from Tuesday (Day 1) to Friday (Day 4)
                    $slot = null;
                    for ($day = 1; $day <= 4; $day++) {
                        $slot = $this->findFreeSlot($lecturerSchedules, $lab->lecturer_id, $weekStart, $day);
                        if ($slot) break;
                    }

                    if ($slot) {
                        // Labs: ~20% chance of being online
                        $mode = (rand(1, 100) <= 20) ? 'online' : 'physical';
                        $method = ($mode === 'online') ? 'qr' : 'ble';
                        $this->createSession($lab->course_id, $lab->id, $lab->lecturer_id, $rooms, $slot['start'], $slot['end'], $method, $mode, $now);
                        $this->markOccupied($lecturerSchedules, $lab->lecturer_id, $slot['start'], $slot['end']);
                    }
                }
            }
        }
    }

    private function findFreeSlot(&$schedules, $lecturerId, $weekStart, $dayOffset)
    {
        $baseDate = $weekStart->copy()->addDays($dayOffset);
        $possibleTimes = [
            ['start' => '08:00', 'end' => '10:00'],
            ['start' => '10:00', 'end' => '12:00'],
            ['start' => '12:00', 'end' => '14:00'],
            ['start' => '14:00', 'end' => '16:00'],
            ['start' => '16:00', 'end' => '18:00'],
        ];

        foreach ($possibleTimes as $time) {
            $start = $baseDate->copy()->setTimeFromTimeString($time['start']);
            $end = $baseDate->copy()->setTimeFromTimeString($time['end']);
            
            if (!$this->isOccupied($schedules, $lecturerId, $start, $end)) {
                return ['start' => $start, 'end' => $end];
            }
        }

        return null;
    }

    private function isOccupied($schedules, $lecturerId, $start, $end)
    {
        if (!isset($schedules[$lecturerId])) return false;
        
        $dateKey = $start->toDateString();
        if (!isset($schedules[$lecturerId][$dateKey])) return false;

        foreach ($schedules[$lecturerId][$dateKey] as $busy) {
            // Check for overlap
            if ($start->lt($busy['end']) && $end->gt($busy['start'])) {
                return true;
            }
        }

        return false;
    }

    private function markOccupied(&$schedules, $lecturerId, $start, $end)
    {
        $dateKey = $start->toDateString();
        $schedules[$lecturerId][$dateKey][] = ['start' => $start, 'end' => $end];
    }

    private function createSession($courseId, $labId, $lecturerId, $rooms, $start, $end, $method, $mode, $now)
    {
        static $roomIndex = 0;
        $roomCount = $rooms->count();

        $isPast = $start->lt($now->copy()->startOfDay());
        $isToday = $start->isToday();
        $is_completed = $isPast || ($isToday && $now->gt($end));
        $is_active = $isToday && $now->between($start, $end);

        $roomId = null;
        if ($mode === 'physical') {
            $roomId = $rooms[$roomIndex % $roomCount]->id;
            $roomIndex++;
        }

        ClassSession::create([
            'course_id'        => $courseId,
            'lab_id'           => $labId,
            'lecturer_id'      => $lecturerId,
            'room_id'          => $roomId,
            'start_time'       => $start,
            'end_time'         => $end,
            'mode'             => $mode,
            'checkin_method'   => $method,
            'is_display'       => $is_active,
            'is_cancelled'     => false,
            'is_completed'     => $is_completed,
            'announce_cancelled' => false,
        ]);
    }
}
