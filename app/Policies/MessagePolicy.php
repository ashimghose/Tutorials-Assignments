<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Messages and their attachments are for the people who can see the conversation.
 */
class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return Gate::forUser($user)->allows('view', $message->conversation);
    }

    public function report(User $user, Message $message): bool
    {
        return $message->sender_id !== $user->id && $message->reported_at === null && $this->view($user, $message);
    }
}
