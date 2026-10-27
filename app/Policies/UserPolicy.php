<?php

namespace App\Policies;

use App\Models\User;

/**
 * The admin panel lists and views accounts. Accounts are never created,
 * edited or deleted there: people sign up, parents add children, admins
 * invite teachers, and deactivation replaces deletion (AUTH-6).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }
}
