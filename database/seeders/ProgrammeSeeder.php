<?php

namespace Database\Seeders;

use App\Models\Programme;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgrammeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programmes = [
            ['code' => 'DIT', 'name' => 'Diploma in Information Technology'],
            ['code' => 'DIM', 'name' => 'Diploma in Multimedia'],
            ['code' => 'DNET', 'name' => 'Diploma in Networking Technology'],
            ['code' => 'DIA', 'name' => 'Diploma in Animation'],
        ];

        foreach ($programmes as $programme) {
            Programme::create($programme);
        }
    }
}
