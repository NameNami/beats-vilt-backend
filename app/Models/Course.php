<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'faculty'])]
class Course extends Model
{
    use HasFactory, SoftDeletes;

    public function labs()
    {
        return $this->hasMany(Lab::class);
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function leaderboardSnapshots()
    {
        return $this->hasMany(LeaderboardSnapshot::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_enrollments')
            ->wherePivot('role', 'student')
            ->withTimestamps();
    }

    public function lecturers()
    {
        return $this->belongsToMany(User::class, 'course_enrollments')
            ->wherePivot('role', 'lecturer')
            ->withTimestamps();
    }
}
