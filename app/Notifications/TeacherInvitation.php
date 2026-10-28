<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TeacherInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public const VALID_DAYS = 7;

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public static function url(User $user): string
    {
        return URL::temporarySignedRoute('invitation.show', now()->addDays(self::VALID_DAYS), ['user' => $user]);
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You are invited to teach with :app', ['app' => config('app.name')]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('You have been invited to join :app as a teacher or expert.', ['app' => config('app.name')]))
            ->action(__('Set your password'), self::url($notifiable))
            ->line(__('This link works once and expires in :days days.', ['days' => self::VALID_DAYS]));
    }
}
