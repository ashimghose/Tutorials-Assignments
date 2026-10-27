<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * CLS-1: admins manage batches. A batch with enrolments is kept for the
 * records; switch it off instead of deleting it.
 */
class BatchPolicy extends AdminPolicy
{
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $model instanceof Batch && ! $model->enrolments()->exists();
    }
}
