<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Notifications\UnreadMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * NOT-1: 15 minutes after a message, emails each member (and the assigned
 * teacher or expert) who hasn't read it. One email per unread stretch;
 * students without an email address are skipped (their parent is a member).
 */
class EmailUnreadMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function handle(): void
    {
        $conversation = $this->message->conversation;
        $recipients = $conversation->members()->get();
        if ($conversation->assigned_to !== null && ! $recipients->contains('id', $conversation->assigned_to)) {
            $recipients->push($conversation->assignee()->first());
        }

        foreach ($recipients->filter() as $user) {
            /** @var User $user */
            if ($user->id === $this->message->sender_id || $user->email === null || ! $user->isActive()) {
                continue;
            }

            $pivot = $conversation->users()->whereKey($user->id)->first()?->getRelation('pivot');
            $readAt = $pivot?->getAttribute('last_read_at') ? Carbon::parse($pivot->getAttribute('last_read_at')) : null;
            $notifiedAt = $pivot?->getAttribute('notified_at') ? Carbon::parse($pivot->getAttribute('notified_at')) : null;

            $unread = $readAt === null || $readAt->lt($this->message->created_at);
            $alreadyEmailed = $notifiedAt !== null && ($readAt === null || $notifiedAt->gt($readAt));

            if ($unread && ! $alreadyEmailed) {
                $user->notify(new UnreadMessage($conversation));
                $conversation->users()->syncWithoutDetaching([$user->id => ['notified_at' => now(), 'is_member' => (bool) ($pivot?->getAttribute('is_member') ?? false)]]);
            }
        }
    }
}
