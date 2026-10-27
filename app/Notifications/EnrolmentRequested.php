<?php

namespace App\Notifications;

use App\Models\Enrolment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the admins that a parent asked for a seat, so they can send the fee link.
 */
class EnrolmentRequested extends Notification implements ShouldQueue
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
        $batch = $this->enrolment->batch;

        return (new MailMessage)
            ->subject('New enrolment request: '.$batch->name())
            ->line("A parent asked for a place for {$this->enrolment->student->first_name} in {$batch->name()}.")
            ->line('Send them the fee link, then mark the fee as received in the admin panel.')
            ->action('Open enrolments', url('/admin/enrolments'));
    }
}
