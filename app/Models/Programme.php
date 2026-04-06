<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'name'])]

class Programme extends Model
{
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
