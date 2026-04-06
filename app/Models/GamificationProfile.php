<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'level_id', 'total_xp', 'total_points', 'current_streak'])]
class GamificationProfile extends Model
{
    protected function casts(): array
    {
        return [
            'total_xp'       => 'integer',
            'total_points'   => 'integer',
            'current_streak' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
