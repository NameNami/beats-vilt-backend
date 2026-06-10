<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name'])]

class Programme extends Model
{
    use Auditable;

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
