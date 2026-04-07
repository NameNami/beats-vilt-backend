<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassSession;
use App\Models\Lab;
use App\Models\Room;
use Illuminate\Support\Carbon;

class ClassSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labs = Lab::with('course')->get();
        $rooms = Room::all();
        if ($rooms->isEmpty() || $labs->isEmpty()) {
            return; // prerequisites not met
        }

        $roomCount = $rooms->count();
        $now = Carbon::now();
        $startOfNextWeek = $now->copy()->next(Carbon::MONDAY)->startOfDay();

        $sessionIndex = 0;
        foreach ($labs as $lab) {
            // Create 4 weekly sessions for each lab
            for ($week = 0; $week < 4; $week++) {
                $dayOffset = $week * 7;
                $start = $startOfNextWeek->copy()->addDays($dayOffset)->setTime(9 + ($sessionIndex % 3) * 2, 0); // 09:00, 11:00, 13:00 pattern
                $end = $start->copy()->addHours(2);

                $room = $rooms[$sessionIndex % $roomCount];

                ClassSession::create([
                    'course_id'        => $lab->course_id,
                    'lab_id'           => $lab->id,
                    'lecturer_id'      => $lab->lecturer_id,
                    'room_id'          => $room->id,
                    'start_time'       => $start,
                    'end_time'         => $end,
                    'mode'             => 'physical',
                    'checkin_method'   => 'BLE',
                    'is_display'       => true,
                    'is_cancelled'     => false,
                    'announce_cancelled' => false,
                ]);

                $sessionIndex++;
            }
        }
    }
}
