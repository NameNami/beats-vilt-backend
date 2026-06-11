<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'device_token', 'device_name'])]
class FcmToken extends Model
{
    use Auditable;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
