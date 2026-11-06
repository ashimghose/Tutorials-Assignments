<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIR-8: tells a teacher or expert who signed up that the admin approved
 * their account.
 */
class TeacherApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your teacher account is approved')
            ->greeting("Hello {$notifiable->name}")
            ->line('Your account with '.config('app.name').' is approved. The batches and orders we give you will appear when you log in.')
            ->action('Log in', route('login'));
    }
}
