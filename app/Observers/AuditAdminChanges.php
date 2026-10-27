<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ADM-6: writes admin edits of content and settings to the audit log. Only
 * the names of changed fields are stored, never their values.
 */
class AuditAdminChanges
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $fields = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));

        if ($fields !== []) {
            $this->record($model, 'updated', ['fields' => $fields]);
        }
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function record(Model $model, string $event, array $properties = []): void
    {
        if (Auth::user()?->isAdmin()) {
            AuditLog::record(Str::snake(class_basename($model)).'.'.$event, $model, $properties);
        }
    }
}
