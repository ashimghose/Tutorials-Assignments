<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1: a message has been waiting unread for 15 minutes. The email does
 * not quote the message, so nothing private sits in an inbox.
 */
class UnreadMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Conversation $conversation) {}

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
            ->subject("New message: {$this->conversation->subject}")
            ->line('You have a new message waiting.')
            ->action('Read it', route('messages.show', $this->conversation));
    }
}
