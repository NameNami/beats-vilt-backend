<?php

namespace App\Services;

use App\Models\User;
use App\Models\GamificationProfile;
use App\Models\Level;
use App\Models\Badge;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    protected $attendanceService;

    public function __construct(AttendanceServices $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Award rewards for a successful attendance check-in.
     */
    public function awardAttendanceRewards(User $user, string $arrivalStatus)
    {
        $xp = $this->attendanceService->calculateXp($arrivalStatus);
        $points = $xp; // For now, points match XP

        $profile = $user->gamificationProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['total_xp' => 0, 'total_points' => 0, 'current_streak' => 0]
        );

        $profile = $profile->fresh();

        DB::transaction(function () use ($profile, $xp, $points, $user, $arrivalStatus) {
            $profile->total_xp += $xp;
            $profile->total_points += $points;
            
            $this->updateStreak($user, $profile);
            $this->checkLevelUp($user, $profile);
            
            $profile->save();
            
            $this->checkBadges($user, $profile, $arrivalStatus);
        });

        return [
            'xp' => $xp,
            'points' => $points,
            'new_total_xp' => $profile->total_xp,
            'level' => $profile->refresh()->level->level ?? '1'
        ];
    }

    /**
     * Update attendance streak logic.
     */
    protected function updateStreak(User $user, GamificationProfile $profile)
    {
        $today = now()->startOfDay();
        
        $lastAttendance = AttendanceRecord::where('user_id', $user->id)
            ->where('status', '!=', 'absent')
            ->where('check_in_time', '<', $today)
            ->orderBy('check_in_time', 'desc')
            ->first();

        if (!$lastAttendance) {
            $profile->current_streak = 1;
            return;
        }

        $lastDate = Carbon::parse($lastAttendance->check_in_time)->startOfDay();

        if ($lastDate->isYesterday()) {
            $profile->current_streak += 1;
        } elseif ($lastDate->isToday()) {
            // Already attended today, do nothing
        } else {
            $profile->current_streak = 1;
        }
    }

    /**
     * Check and update user level.
     */
    protected function checkLevelUp(User $user, GamificationProfile $profile)
    {
        $currentXp = $profile->total_xp;
        
        $newLevel = Level::where('xp_required', '<=', $currentXp)
            ->orderBy('xp_required', 'desc')
            ->first();

        if ($newLevel && $newLevel->id !== $profile->level_id) {
            $profile->update(['level_id' => $newLevel->id]);
            // Optional: Trigger level up notification
        }
    }

    /**
     * Check and award badges.
     */
    protected function checkBadges(User $user, GamificationProfile $profile, string $arrivalStatus)
    {
        $badges = Badge::all();
        $userBadgeIds = $user->badges()->pluck('badge_id')->toArray();

        foreach ($badges as $badge) {
            if (in_array($badge->id, $userBadgeIds)) continue;

            $shouldAward = false;

            switch ($badge->requirement_type) {
                case 'early_checkins':
                    $count = AttendanceRecord::where('user_id', $user->id)->where('status', 'early')->count();
                    if ($count >= $badge->requirement_value) $shouldAward = true;
                    break;
                case 'on_time_checkins':
                    $count = AttendanceRecord::where('user_id', $user->id)->where('status', 'on-time')->count();
                    if ($count >= $badge->requirement_value) $shouldAward = true;
                    break;
                case 'streak_count':
                    if ($profile->current_streak >= $badge->requirement_value) $shouldAward = true;
                    break;
                case 'total_xp':
                    if ($profile->total_xp >= $badge->requirement_value) $shouldAward = true;
                    break;
            }

            if ($shouldAward) {
                $user->badges()->attach($badge->id);
            }
        }
    }
}
