<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'room_id', 'uuid', 'mac_address', 'rssi_threshold', 'status', 'battery', 'last_seen'])]
class Beacon extends Model
{
    protected function casts(): array
    {
        return [
            'rssi_threshold' => 'integer',
            'battery' => 'integer',
            'last_seen' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
