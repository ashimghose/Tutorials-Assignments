<?php

namespace App\Notifications;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1: welcome email once a parent or academic student verifies their email.
 */
class Welcome extends Notification implements ShouldQueue
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
        $message = (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting("Welcome, {$notifiable->name}");

        return $notifiable->role === Role::Parent
            ? $message->line('Add your child, then choose their classes. You can message us any time from your account.')->action('Add your child', route('parent.children'))
            : $message->line('You can order guidance, editing, formatting, a similarity check or printing for your research work, and track every step.')->action('Go to my orders', route('academic.home'));
    }
}
