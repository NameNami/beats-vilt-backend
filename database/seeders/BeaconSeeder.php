<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Beacon;
use App\Models\Room;

class BeaconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = Room::all();
        foreach ($rooms as $room) {
            Beacon::factory()->create([
                'room_id' => $room->id,
            ]);
        }
    }
}
