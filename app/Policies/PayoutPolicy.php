<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * ADM-2: a payout keeps what was paid. Admins can delete one marked by mistake, not edit it.
 */
class PayoutPolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }
}
