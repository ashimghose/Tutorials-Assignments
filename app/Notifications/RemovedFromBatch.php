<?php

namespace App\Notifications;

use App\Models\Enrolment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * CLS-7, NOT-1: the child's place in a batch has ended.
 */
class RemovedFromBatch extends Notification implements ShouldQueue
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
        $batch = $this->enrolment->batch->name();

        return (new MailMessage)
            ->subject("Removed from {$batch}")
            ->line("{$this->enrolment->student->first_name} has been removed from {$batch}.")
            ->line('Reason: '.$this->enrolment->removed_reason)
            ->line('Please message us if you have any questions.');
    }
}
