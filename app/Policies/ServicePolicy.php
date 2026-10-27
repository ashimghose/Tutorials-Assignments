<?php

namespace App\Policies;

use App\Models\AcademicOrder;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * ACA-1: admins manage the price list. A service that has orders is switched
 * off instead of deleted.
 */
class ServicePolicy extends AdminPolicy
{
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $model instanceof Service && ! AcademicOrder::query()->where('service_id', $model->id)->exists();
    }
}
