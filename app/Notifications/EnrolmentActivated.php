<?php

namespace App\Notifications;

use App\Models\Enrolment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1: the fee is confirmed and the child can join classes.
 */
class EnrolmentActivated extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("{$name} is enrolled in {$this->enrolment->batch->name()}")
            ->line("Thank you. We have received the fee, and {$name} can join the classes of {$this->enrolment->batch->name()}.")
            ->line('Paid until: '.$this->enrolment->paid_through?->format('j M Y'))
            ->action('See the timetable', route('parent.classes'));
    }
}
