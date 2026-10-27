<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Leads come only from the site's forms and are kept as the record of
 * interest (MKT-5); the admin follows them up.
 */
class LeadPolicy extends AdminPolicy
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
