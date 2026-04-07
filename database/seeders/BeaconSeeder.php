<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Beacon;
use App\Models\Room;
use Illuminate\Support\Str;

class BeaconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = Room::all();
        foreach ($rooms as $room) {
            Beacon::create([
                'room_id' => $room->id,
                'uuid' => (string) Str::uuid(),
                'rssi_threshold' => -70,
                'is_active' => true,
            ]);
        }
    }
}
