<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            ProgrammeSeeder::class,
            LevelSeeder::class,
            SystemSettingSeeder::class,
            RoomSeeder::class,
            BadgeSeeder::class,
            RewardSeeder::class,
            UserSeeder::class,
            CourseSeeder::class,
            BeaconSeeder::class,
            LabSeeder::class,
            CourseEnrollmentSeeder::class,
            ClassSessionSeeder::class,
            AttendanceSeeder::class,
            LeaveApplicationSeeder::class,
            GamificationSeeder::class,
            LecturerTimetableSeeder::class,
        ]);
    }
}
