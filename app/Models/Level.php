<?php

namespace App\Models;


use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['level', 'xp_required'])]
class Level extends Model
{
    use Auditable;

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
