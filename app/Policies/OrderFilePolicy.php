<?php

namespace App\Policies;

use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * An order's files are for the people who can see the order.
 */
class OrderFilePolicy
{
    public function download(User $user, OrderFile $file): bool
    {
        return Gate::forUser($user)->allows('view', $file->order);
    }
}
