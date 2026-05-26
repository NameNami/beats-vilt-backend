<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['room_id', 'uuid', 'mac_address', 'rssi_threshold', 'status', 'last_seen'])]
class Beacon extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'rssi_threshold' => 'integer',
            'last_seen' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
