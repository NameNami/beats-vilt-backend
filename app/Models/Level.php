<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['level_number', 'name', 'xp_required', 'icon_path'])]
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
