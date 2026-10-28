<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Parents see and manage only their own children (AUTH-3); admins see all.
 */
class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->parentProfile !== null || $user->isAdmin();
    }

    public function view(User $user, Student $student): bool
    {
        return $this->ownsChild($user, $student) || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->parentProfile !== null && $user->hasVerifiedEmail();
    }

    public function update(User $user, Student $student): bool
    {
        return $this->ownsChild($user, $student);
    }

    private function ownsChild(User $user, Student $student): bool
    {
        return $user->parentProfile !== null && $student->parent_id === $user->parentProfile->id;
    }
}
