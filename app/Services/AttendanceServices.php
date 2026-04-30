<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ClassSession;

class AttendanceServices
{
    public function classifyArrival(ClassSession $session, string $checkInTimestamp)
    {
        $checkInTime = Carbon::parse($checkInTimestamp);
        $start = $session->start_time;
        $end = $session->end_time;
        $onTimeThreshold = $session->start_time->copy()->addMinutes(10); // TODO: make this configurable using settings
        $earlyThreshold = $session->start_time->copy()->subMinutes(30); // TODO: make this configurable using settings

        if ($checkInTime->between($start, $end)) // if scan with in the class time
        {
            if ($checkInTime < $onTimeThreshold) // if timestamp checkin is under the on time threshold then its on time
            {
                return 'on time';
            }
            else { // else its late
                return 'late';
            }
        }
        elseif ($checkInTime->between($earlyThreshold, $start)) // if scan with in the early time threshold
        {
            return 'early';
        }
        else
        {
            return 'invalid';
        }
    }

    public function calculateXp(string $classification): int
    {
        return match ($classification) {
            'on-time' => 50,
            'late' => 20,
            'early' => 10,
            default => 0,
        };
    }
}
