<?php

namespace App\Notifications;

use App\Models\Enrolment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PAY-4: the paid-through date is coming up.
 */
class FeeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enrolment $enrolment, public int $daysLeft) {}

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
        $when = $this->daysLeft === 1 ? 'tomorrow' : "in {$this->daysLeft} days";

        return (new MailMessage)
            ->subject("Fee due {$when}: {$batch}")
            ->line("{$this->enrolment->student->first_name}'s fee for {$batch} is paid until {$this->enrolment->paid_through?->format('j M Y')}.")
            ->line('Please renew so classes continue without a break. We will send you the payment link on WhatsApp, or you can message us.')
            ->action('See enrolments', route('parent.classes'));
    }
}
