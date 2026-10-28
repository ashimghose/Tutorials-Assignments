<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Teachers are invited from People (AUTH-4) and deactivated there, not
 * created or deleted here.
 */
class TeacherPolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
