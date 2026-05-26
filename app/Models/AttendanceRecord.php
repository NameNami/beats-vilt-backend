<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }
}
