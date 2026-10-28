<?php

namespace App\Notifications;

use App\Models\Enrolment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PAY-4, NOT-1: the grace period ended without a renewal.
 */
class AccessPaused extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enrolment $enrolment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->enrolment->student->first_name;
        $batch = $this->enrolment->batch->name();

        return (new MailMessage)
            ->subject("Classes paused: {$batch}")
            ->line("The fee for {$name}'s place in {$batch} has not been renewed, so {$name} can't join classes for now.")
            ->line('Renew the fee and access starts again straight away. Message us if you need help.')
            ->action('See enrolments', route('parent.classes'));
    }
}
