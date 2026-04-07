<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['level' => '1', 'xp_required' => '0'],
            ['level' => '2', 'xp_required' => '30'],
            ['level' => '3', 'xp_required' => '70'],
            ['level' => '4', 'xp_required' => '110'],
            ['level' => '5', 'xp_required' => '155'],
            ['level' => '6', 'xp_required' => '190'],
            ['level' => '7', 'xp_required' => '220'],
            ['level' => '8', 'xp_required' => '265'],
            ['level' => '9', 'xp_required' => '310'],
        ];

        foreach ($levels as $level) {
            Level::create($level);
        }
    }
}
