<?php

namespace App\Policies;

use App\Models\User;

/**
 * Payments are recorded by "Fee received" and "Payment received" (later by
 * the gateway), not created on their own. Admins can correct or delete one.
 */
class PaymentPolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
