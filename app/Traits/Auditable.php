<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::logAuditAction('created', $model);
        });

        static::updated(function (Model $model) {
            self::logAuditAction('updated', $model);
        });

        static::deleted(function (Model $model) {
            self::logAuditAction('deleted', $model);
        });
    }

    protected static function logAuditAction(string $action, Model $model)
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'created') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'updated') {
            $changes = $model->getChanges();
            $oldValues = array_intersect_key($model->getOriginal(), $changes);
            $newValues = $changes;

            // Unset timestamp columns if they are the only things changed
            unset($oldValues['updated_at'], $newValues['updated_at']);

            if (empty($newValues)) {
                return;
            }
        } elseif ($action === 'deleted') {
            $oldValues = $model->getAttributes();
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id ?? 0, // Fallback if no ID available during delete? Actually it should have ID
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
