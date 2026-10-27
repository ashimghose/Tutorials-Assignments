<?php

namespace App\Notifications;

use App\Models\Notice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-2: a notice from the admin, also shown on the dashboard.
 */
class NoticePublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Notice $notice) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->notice->title)
            ->line($this->notice->body)
            ->action('Open your dashboard', route('dashboard'));
    }
}
