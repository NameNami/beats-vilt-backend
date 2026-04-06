<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable(['name', 'capacity', 'location'])]
class Room extends Model
{
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }
    public function beacons()
    {
        return $this->hasMany(Beacon::class);
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }
}
