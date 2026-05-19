<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ClassSession;
use App\Models\AttendanceRecord;
use App\Models\CourseEnrollment;
use App\Models\QrToken;
use App\Models\User;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;

class AttendanceServices
{
    public function classifyArrival(ClassSession $session, string $checkInTimestamp): String
    {
        $checkInTime = Carbon::createFromTimestamp($checkInTimestamp);
        $start = $session->start_time;
        $end = $session->end_time;
        $onTimeThreshold = $session->start_time->copy()->addMinutes(10); // TODO: make this configurable using settings
        $earlyThreshold = $session->start_time->copy()->subMinutes(30); // TODO: make this configurable using settings

        // check if the timestamp is within the past 3 minutes and the future 3 minutes to stop manipulated timestamps
        $pastThreshold = now()->subMinutes(3);
        $futureBuffer = now()->addMinutes(3);
        if (! $checkInTime->between($pastThreshold, $futureBuffer))
        {
            abort(400, 'Invalid timestamp');
        }

        if ($checkInTime->between($start, $end)) // if scan with in the class time
        {
            if ($checkInTime < $onTimeThreshold) // if timestamp checkin is under the on time threshold then its on time
            {
                return 'on-time';
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
            'late' => 35,
            'early' => 100,
            'present' => 100,
            default => 0,
        };
    }

    public function checkInValidationQr(ClassSession $session, string $checkInTimestamp, string $qrToken, User $user): array
    {
        // check if the class within the timeframe | gonna use clasifyArrival()

        // check if qr token is valid (not expired and within the token table)
        $qrToken = QrToken::where('token', $qrToken)->first();
        if (! $qrToken)
        {
            return ['status' => false, 'message' => 'Invalid QR token', 'code' => 403];
        }

        if ($qrToken->session_id != $session->id)
        {
            return ['status' => false, 'message' => 'Invalid QR token', 'code' => 403];
        }

        // check the qr token expiration
        $expiredAt = Carbon::parse($qrToken->expires_at);
        $checkInTime = Carbon::createFromTimestamp($checkInTimestamp);
        if ($expiredAt->lte($checkInTime))
        {
            return ['status' => false, 'message' => 'QR token has expired', 'code' => 410];
        }

        // check if the class is cancelled
        if ($session->is_cancelled)
        {
            return ['status' => false, 'message' => 'Class is cancelled', 'code' => 403];
        }

        // check if lecturer is displaying the qr code
        if (! $session->is_display)
        {
            return ['status' => false, 'message' => 'QR code is not displayed', 'code' => 403];
        }

        // check if user already checked in
        if ($session->attendanceRecords()->where('user_id', $user->id)->exists()) {
            return ['status' => false, 'message' => 'You have already checked in.', 'code' => 409];
        }

        return ['status' => true, 'message' => 'Valid QR token'];
    }

    // ---- Cron Job ----

    public function GenerateAbsentStatus()
    {
        // if class_session end time < now(), and the user is not have record in attendance_record than create record with absent status and mark the class as completed
        // create record in attendance_record | doneee
        // check any class session is completed and theres student with no record in attendance_record | yup done
        // create cron job to check and update absent status | done with pristineeee

        // array of class session that ended and not completed for marking the student absent
        $endedClasses = ClassSession::with('attendanceRecords')->where('end_time', '<', now())->where('is_completed', false)->get();

        foreach ($endedClasses as $endedClass) {
            $expectedStudentIds = CourseEnrollment::where('course_id', $endedClass->course_id)
                ->when($endedClass->lab_id, function ($query, $labId) {
                    return $query->where('lab_id', $labId);
                })
                ->where('role', 'student')
                ->pluck('user_id');
            // get all present student id from attendance record
            $presentStudentIds = $endedClass->attendanceRecords->pluck('user_id')->unique();
            // then we compare the student list with the attendance record list
            $absentStudentIds = array_diff($expectedStudentIds->toArray(), $presentStudentIds->toArray());

            // gather all absent student to insert into attendance record
            $recordsToInsert = [];
            foreach ($absentStudentIds as $absentStudentId)
            {
                $recordsToInsert[] = [
                    'user_id' => $absentStudentId,
                    'session_id' => $endedClass->id,
                    'check_in_time' => now(),
                    'status' => 'absent',
                    'checkin_method' => $endedClass->checkin_method,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // check if there is any absent student to insert and then insert in bulk
            if (!empty($recordsToInsert))
            {
                AttendanceRecord::insert($recordsToInsert);
            }

            // mark the class as completed settled cantek
            $endedClass->update(['is_completed' => true]);
        }
    }

    public function averageAttendance()
    {
        //
    }

    public function classTodayCount()
    {
        //
    }

    public function pendingLeaveCount()
    {
        //
    }

    public function atRiskStudentCount()
    {
        //
    }
}
