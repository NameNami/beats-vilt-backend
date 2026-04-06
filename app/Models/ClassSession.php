<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'course_id',
    'lab_id',
    'lecturer_id',
    'room_id',
    'start_time',
    'end_time',
    'mode',
    'checkin_method',
    'is_display',
    'is_cancelled',
    'announce_cancelled',
])]

class ClassSession extends Model
{
    use HasFactory, SoftDeletes;
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_display'         => 'boolean',
            'is_cancelled'       => 'boolean',
            'announce_cancelled' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }
    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'session_id');
    }
    public function qrTokens()
    {
        return $this->hasMany(QrToken::class, 'session_id');
    }
    public function activeQrToken()
    {
        return $this->hasOne(QrToken::class, 'session_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }
}
