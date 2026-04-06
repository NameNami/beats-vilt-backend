<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'session_id', 'check_in_time', 'status', 'checkin_method'])]
class AttendanceRecord extends Model
{
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
