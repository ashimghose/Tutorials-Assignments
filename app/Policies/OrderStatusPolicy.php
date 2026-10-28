<?php

namespace App\Policies;

use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * ACA-6: admins manage the status list. A status that an order uses, or
 * used, stays for the history.
 */
class OrderStatusPolicy extends AdminPolicy
{
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $model instanceof OrderStatus
            && ! $model->orders()->exists()
            && ! OrderStatusHistory::query()->where('to_status_id', $model->id)->exists();
    }
}
