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

Schedule::command('attendance:send-at-risk-notifications')
    ->weeklyOn(0, '23:55') // Sundays at 23:55
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/at-risk-notifications.log'));

Schedule::command('app:change-beacon-status')
    ->everyMinute();

Schedule::command('app:renew-beacon-uuid')
    ->everyFiveMinutes();

Schedule::command('gamification:snapshot-leaderboard')
    ->weeklyOn(0, '23:59') // Runs every Sunday at 23:59
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/leaderboard-snapshot.log'));
