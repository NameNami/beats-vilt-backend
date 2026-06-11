<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'capacity', 'location'])]
class Room extends Model
{
    use Auditable;

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
