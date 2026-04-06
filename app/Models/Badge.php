<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'description',
    'icon_path',
    'type',
    'requirement_type',
    'requirement_value',
])]
class Badge extends Model
{
    protected function casts(): array
    {
        return ['requirement_value' => 'integer'];
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withTimestamps();
    }
}
