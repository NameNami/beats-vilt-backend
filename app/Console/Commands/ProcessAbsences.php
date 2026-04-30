<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\AttendanceServices;

#[AsCommand(name: 'attendance:process-absences', description: 'Marks missing students as absent.')]
class ProcessAbsences extends Command
{
    public function handle(AttendanceServices $attendanceService)
    {
        $this->info('Attendance processing started');

        $attendanceService->GenerateAbsentStatus();

        $this->info('Attendance processing completed');

    }
}
