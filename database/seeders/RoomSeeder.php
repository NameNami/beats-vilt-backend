<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['name' => 'Lab 1', 'capacity' => 30, 'location' => 'Block A, Level 1'],
            ['name' => 'Lab 2', 'capacity' => 30, 'location' => 'Block A, Level 1'],
            ['name' => 'Lecture Hall 1', 'capacity' => 80, 'location' => 'Block B, Level 2'],
            ['name' => 'Lecture Hall 2', 'capacity' => 80, 'location' => 'Block B, Level 2'],
            ['name' => 'Seminar Room', 'capacity' => 40, 'location' => 'Block C, Level 3'],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
