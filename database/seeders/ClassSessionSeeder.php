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

        $sessionIndex = 0;
        foreach ($labs as $lab) {
            // Create a mix of sessions: 1 Past, 1 Ongoing, 2 Future
            
            // 1. Past Session (Completed)
            $startPast = $now->copy()->subDays(2)->setTime(10, 0);
            $endPast = $startPast->copy()->addHours(2);
            ClassSession::create([
                'course_id'        => $lab->course_id,
                'lab_id'           => $lab->id,
                'lecturer_id'      => $lab->lecturer_id,
                'room_id'          => $rooms[$sessionIndex % $roomCount]->id,
                'start_time'       => $startPast,
                'end_time'         => $endPast,
                'mode'             => 'physical',
                'checkin_method'   => 'ble',
                'is_display'       => true,
                'is_cancelled'     => false,
                'is_completed'     => true,
                'announce_cancelled' => false,
            ]);

            // 2. Ongoing Session (Active)
            $startOngoing = $now->copy()->subHour();
            $endOngoing = $startOngoing->copy()->addHours(2);
            ClassSession::create([
                'course_id'        => $lab->course_id,
                'lab_id'           => $lab->id,
                'lecturer_id'      => $lab->lecturer_id,
                'room_id'          => $rooms[($sessionIndex + 1) % $roomCount]->id,
                'start_time'       => $startOngoing,
                'end_time'         => $endOngoing,
                'mode'             => 'physical',
                'checkin_method'   => 'ble',
                'is_display'       => true,
                'is_cancelled'     => false,
                'is_completed'     => false,
                'announce_cancelled' => false,
            ]);

            // 3. Future Session (Upcoming)
            $startFuture = $now->copy()->addDays(1)->setTime(9, 0);
            $endFuture = $startFuture->copy()->addHours(2);
            ClassSession::create([
                'course_id'        => $lab->course_id,
                'lab_id'           => $lab->id,
                'lecturer_id'      => $lab->lecturer_id,
                'room_id'          => $rooms[($sessionIndex + 2) % $roomCount]->id,
                'start_time'       => $startFuture,
                'end_time'         => $endFuture,
                'mode'             => 'physical',
                'checkin_method'   => 'ble',
                'is_display'       => true,
                'is_cancelled'     => false,
                'is_completed'     => false,
                'announce_cancelled' => false,
            ]);

            // 4. Cancelled Session
            $startCancelled = $now->copy()->addDays(2)->setTime(14, 0);
            $endCancelled = $startCancelled->copy()->addHours(2);
            ClassSession::create([
                'course_id'        => $lab->course_id,
                'lab_id'           => $lab->id,
                'lecturer_id'      => $lab->lecturer_id,
                'room_id'          => $rooms[($sessionIndex + 3) % $roomCount]->id,
                'start_time'       => $startCancelled,
                'end_time'         => $endCancelled,
                'mode'             => 'physical',
                'checkin_method'   => 'ble',
                'is_display'       => true,
                'is_cancelled'     => true,
                'is_completed'     => false,
                'announce_cancelled' => true,
            ]);

            $sessionIndex++;
        }
    }
}
