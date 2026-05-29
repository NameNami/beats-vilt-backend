<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:process-absences')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-cron.log'));

Schedule::command('attendance:prune-expired-qr-token')
    ->everyMinute();

Schedule::command('app:change-beacon-status')
    ->everyMinute();

Schedule::command('app:renew-beacon-uuid')
    ->everyFiveMinutes();
