<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'course_id', 'period_type', 'xp_at_snapshot', 'rank'])]
class LeaderboardSnapshot extends Model
{
    const CREATED_AT = 'created_at';
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'xp_at_snapshot' => 'integer',
            'rank' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
