<?php

namespace App\Console\Commands;

use App\Services\AttendanceServices;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;

// #[AsCommand(name: 'attendance:process-absences', description: 'Marks missing students as absent.')]
class ProcessAbsences extends Command
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
