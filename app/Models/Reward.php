<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'cost_points', 'stock', 'is_active'])]
class Reward extends Model
{
    protected function casts(): array
    {
        return [
            'cost_points' => 'integer',
            'is_active' => 'boolean',
            'stock'     => 'integer',
        ];
    }

    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }
}
