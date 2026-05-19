<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\CourseEnrollment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\AttendanceServices;

//#[AsCommand(name: 'attendance:process-absences', description: 'Marks missing students as absent.')]
class npmProcessAbsences extends Command
{
    protected $signature = 'attendance:process-absences';
    protected $description = 'Marks missing students as absent for ended classes.';
    public function handle(AttendanceServices $attendanceService)
    {
        $this->info('Attendance processing started');

        $attendanceService->GenerateAbsentStatus();

        $this->info('Attendance processing completed');

    }
}
