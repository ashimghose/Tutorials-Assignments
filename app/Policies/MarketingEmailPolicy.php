<?php

namespace App\Policies;

use App\Models\MarketingEmail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * NOT-3: admins write and send marketing emails; a sent email is kept as a record.
 */
class MarketingEmailPolicy extends AdminPolicy
{
    /**
     * @param  MarketingEmail  $model
     */
    public function update(User $user, Model $model): bool
    {
        return $user->isAdmin() && $model->sent_at === null;
    }

    /**
     * @param  MarketingEmail  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    public function send(User $user, MarketingEmail $email): bool
    {
        return $this->update($user, $email);
    }
}
