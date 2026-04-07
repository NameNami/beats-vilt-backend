<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['level', 'xp_required'])]
class Level extends Model
{
    protected function casts(): array
    {
        return [
            'xp_required' => 'integer',
        ];
    }
    public function gamificationProfiles()
    {
        return $this->hasMany(GamificationProfile::class);
    }
}
