<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['room_id', 'uuid', 'rssi_threshold', 'is_active', 'last_seen'])]
class Beacon extends Model
{
    protected function casts(): array
    {
        return [
            'rssi_threshold' => 'integer',
            'is_active' => 'boolean',
            'last_seen' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
