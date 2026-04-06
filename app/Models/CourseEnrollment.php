<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'course_id', 'lab_id', 'role'])]
class CourseEnrollment extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
}
