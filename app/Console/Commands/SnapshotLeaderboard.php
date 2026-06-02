<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use App\Models\GamificationProfile;
use App\Models\LeaderboardSnapshot;
use App\Models\CourseEnrollment;
use Illuminate\Support\Facades\DB;

class SnapshotLeaderboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:snapshot-leaderboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a snapshot of the leaderboard (both global and per-course) for the current week.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting leaderboard snapshot process...');

        DB::transaction(function () {
            $timestamp = now();

            // 1. Snapshot Global Leaderboard (null course_id)
            $this->info('Processing global leaderboard...');
            $globalProfiles = GamificationProfile::with('user')
                ->whereHas('user', function($q) {
                    $q->where('role', 'student');
                })
                ->orderByDesc('total_xp')
                ->get();

            $rank = 1;
            $snapshots = [];
            foreach ($globalProfiles as $profile) {
                $snapshots[] = [
                    'user_id' => $profile->user_id,
                    'course_id' => null,
                    'period_type' => 'weekly',
                    'xp_at_snapshot' => $profile->total_xp,
                    'rank' => $rank++,
                    'created_at' => $timestamp,
                ];
            }

            if (!empty($snapshots)) {
                LeaderboardSnapshot::insert($snapshots);
            }

            // 2. Snapshot Per-Course Leaderboards
            $courses = Course::all();
            
            foreach ($courses as $course) {
                $this->info("Processing leaderboard for course: {$course->code}");
                
                // Get students enrolled in this course
                $enrolledStudentIds = CourseEnrollment::where('course_id', $course->id)
                    ->where('role', 'student')
                    ->pluck('user_id');

                if ($enrolledStudentIds->isEmpty()) {
                    continue;
                }

                // Rank those students based on their gamification profile XP
                $courseProfiles = GamificationProfile::whereIn('user_id', $enrolledStudentIds)
                    ->orderByDesc('total_xp')
                    ->get();

                $rank = 1;
                $courseSnapshots = [];
                foreach ($courseProfiles as $profile) {
                    $courseSnapshots[] = [
                        'user_id' => $profile->user_id,
                        'course_id' => $course->id,
                        'period_type' => 'weekly',
                        'xp_at_snapshot' => $profile->total_xp,
                        'rank' => $rank++,
                        'created_at' => $timestamp,
                    ];
                }

                if (!empty($courseSnapshots)) {
                    LeaderboardSnapshot::insert($courseSnapshots);
                }
            }
        });

        $this->info('Leaderboard snapshot process completed successfully.');
    }
}
