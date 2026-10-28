<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * CHAT-2: tells the admins a new conversation is waiting in the inbox.
 */
class NewConversation extends Notification implements ShouldQueue
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
            ->subject("New {$this->conversation->offering->label()} message: {$this->conversation->subject}")
            ->line('A new conversation is waiting in the inbox.')
            ->action('Open the inbox', route('messages.show', $this->conversation));
    }
}
