<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\User;

/**
 * CHAT-2, CHAT-4: who may see and write in a conversation. Teachers and
 * experts can't start conversations, so there are no private
 * teacher-to-minor channels.
 */
class ConversationPolicy
{
    /**
     * The admin panel's inbox list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return Conversation::query()->visibleTo($user)->whereKey($conversation->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $this->useMessages($user)
            && ($user->parentProfile !== null || $user->student !== null || $user->academicStudent !== null);
    }

    /**
     * CHAT-4, MUS-4: an under-18 music learner has no parent account to add
     * to a conversation, so they don't get chat; they reach us on WhatsApp.
     */
    public function useMessages(User $user): bool
    {
        return ! ($user->hasRole(Role::MusicStudent) && ($user->student?->isMinorMusicLearner() ?? true));
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        return ! $conversation->isClosed() && $this->view($user, $conversation);
    }

    /**
     * CHAT-2: only admins assign, close and reopen.
     */
    public function manage(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }
}
