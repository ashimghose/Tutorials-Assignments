<?php

namespace App\Policies;

use App\Models\User;

/**
 * Applications come only from the Careers form (HIR-2). The admin reads and
 * follows them up; they are deleted automatically after a year (HIR-5).
 */
class JobApplicationPolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
