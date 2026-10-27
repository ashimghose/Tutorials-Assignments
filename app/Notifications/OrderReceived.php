<?php

namespace App\Notifications;

use App\Models\AcademicOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1: the student's order or quote request was received.
 */
class OrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AcademicOrder $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("We have your order {$this->order->reference()}")
            ->line("Thank you. We have received your order for {$this->order->service_title}.");

        return ($this->order->is_quote
            ? $message->line('We will send you a quote shortly.')
            : $message->line('We will send you the payment link on WhatsApp. Work starts once the payment is received.'))
            ->action('Track your order', route('academic.orders.show', $this->order));
    }
}
