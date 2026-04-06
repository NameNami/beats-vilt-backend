<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['session_id', 'token', 'expires_at'])]
class QrToken extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }
}
