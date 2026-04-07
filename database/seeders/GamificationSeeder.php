<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\GamificationProfile;

class GamificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a gamification profile for every user (idempotent)
        $users = User::all();
        foreach ($users as $user) {
            GamificationProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'level_id'       => null, // start without a level; can be computed later based on XP
                    'total_xp'       => 0,
                    'total_points'   => 0,
                    'current_streak' => 0,
                ]
            );
        }
    }
}
