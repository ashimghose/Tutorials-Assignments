<?php

namespace App\Policies;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * NOT-2: admins write notices; a published notice has been emailed, so it can't be edited.
 */
class NoticePolicy extends AdminPolicy
{
    /**
     * @param  Notice  $model
     */
    public function update(User $user, Model $model): bool
    {
        return $user->isAdmin() && $model->published_at === null;
    }

    public function publish(User $user, Notice $notice): bool
    {
        return $this->update($user, $notice);
    }
}
