<?php

namespace Database\Factories;

use App\Models\Beacon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BeaconFactory extends Factory
{
    protected $model = Beacon::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'mac_address' => $this->faker->unique()->macAddress(),
            'rssi_threshold' => -70,
            'status' => 'active',
            'last_seen' => now(),
        ];
    }
}
