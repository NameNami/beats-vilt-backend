<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name'              => 'Early Bird',
                'description'       => 'Check in early 5 times',
                'icon_path'         => null,
                'type'              => 'attendance',
                'requirement_type'  => 'early_checkins',
                'requirement_value' => 5,
            ],
            [
                'name'              => 'Punctual Pro',
                'description'       => 'Check in on time 10 times',
                'icon_path'         => null,
                'type'              => 'attendance',
                'requirement_type'  => 'on_time_checkins',
                'requirement_value' => 10,
            ],
            [
                'name'              => 'Perfect Month',
                'description'       => 'Attend all sessions in a month',
                'icon_path'         => null,
                'type'              => 'attendance',
                'requirement_type'  => 'early_checkins',
                'requirement_value' => 20,
            ],

            // Streak based
            [
                'name'              => 'On A Roll',
                'description'       => 'Maintain a 5 day streak',
                'icon_path'         => null,
                'type'              => 'streak',
                'requirement_type'  => 'streak_count',
                'requirement_value' => 5,
            ],
            [
                'name'              => 'Streak Master',
                'description'       => 'Maintain a 10 day streak',
                'icon_path'         => null,
                'type'              => 'streak',
                'requirement_type'  => 'streak_count',
                'requirement_value' => 10,
            ],
            [
                'name'              => 'Unstoppable',
                'description'       => 'Maintain a 30 day streak',
                'icon_path'         => null,
                'type'              => 'streak',
                'requirement_type'  => 'streak_count',
                'requirement_value' => 30,
            ],

            // XP based
            [
                'name'              => 'First Steps',
                'description'       => 'Earn your first 100 XP',
                'icon_path'         => null,
                'type'              => 'xp',
                'requirement_type'  => 'total_xp',
                'requirement_value' => 100,
            ],
            [
                'name'              => 'Centurion',
                'description'       => 'Reach 500 XP',
                'icon_path'         => null,
                'type'              => 'xp',
                'requirement_type'  => 'total_xp',
                'requirement_value' => 500,
            ],
            [
                'name'              => 'XP Legend',
                'description'       => 'Reach 2000 XP',
                'icon_path'         => null,
                'type'              => 'xp',
                'requirement_type'  => 'total_xp',
                'requirement_value' => 2000,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::create($badge);
        }
    }
}
