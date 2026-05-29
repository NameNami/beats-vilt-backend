<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\GamificationProfile;
use App\Models\AttendanceRecord;
use App\Models\Level;
use App\Services\AttendanceServices;

class GamificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendanceService = new AttendanceServices();
        $levels = Level::orderBy('xp_required', 'desc')->get();

        // Create a gamification profile for every user
        $users = User::all();
        foreach ($users as $user) {
            $totalXp = 0;
            $attendanceRecords = AttendanceRecord::where('user_id', $user->id)->get();
            
            foreach ($attendanceRecords as $record) {
                $totalXp += $attendanceService->calculateXp($record->status);
            }

            // Determine level
            $levelId = null;
            foreach ($levels as $level) {
                if ($totalXp >= $level->xp_required) {
                    $levelId = $level->id;
                    break;
                }
            }

            GamificationProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'level_id'       => $levelId,
                    'total_xp'       => $totalXp,
                    'total_points'   => $totalXp, // Points match XP for now
                    'current_streak' => rand(1, 5), // Randomize streak for variety
                ]
            );
        }
    }
}
