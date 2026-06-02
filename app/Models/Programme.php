<?php

namespace App\Models;


use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'name'])]

class Programme extends Model
{
    use Auditable;

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
