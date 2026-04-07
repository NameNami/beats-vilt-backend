<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\BeaconSeeder;
use Database\Seeders\ClassSessionSeeder;
use Database\Seeders\CourseEnrollmentSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\GamificationSeeder;
use Database\Seeders\LabSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\ProgrammeSeeder;
use Database\Seeders\RewardSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\SystemSettingSeeder;
use Database\Seeders\UserSeeder;

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
            GamificationSeeder::class,
        ]);
    }
}
