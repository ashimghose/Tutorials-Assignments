<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1: a class was cancelled or moved.
 */
class ClassChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ClassSession $session, public bool $cancelled) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tz = config('app.display_timezone');
        $batch = $this->session->batch->name();
        $when = $this->session->starts_at->setTimezone($tz)->format('D j M, g:i a');

        return $this->cancelled
            ? (new MailMessage)
                ->subject("Class cancelled: {$batch}")
                ->line("The {$batch} class on {$when} is cancelled.")
                ->line('The next classes go ahead as planned.')
            : (new MailMessage)
                ->subject("Class moved: {$batch}")
                ->line("The {$batch} class has moved to {$when}.")
                ->action('See the timetable', url('/dashboard'));
    }
}
