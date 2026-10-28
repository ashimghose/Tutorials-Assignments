<?php

namespace App\Actions\Notices;

use App\Models\Notice;
use App\Models\User;
use App\Notifications\NoticePublished;
use Illuminate\Support\Facades\Notification;

/**
 * NOT-2: publishes a notice on the audience's dashboards and emails them.
 */
class PublishNotice
{
    public function handle(Notice $notice, User $admin): int
    {
        $notice->forceFill(['published_at' => now(), 'created_by' => $notice->created_by ?? $admin->id])->save();

        $recipients = $notice->recipients()->filter(fn (User $user) => $user->email !== null);
        Notification::send($recipients, new NoticePublished($notice));

        return $recipients->count();
    }
}
