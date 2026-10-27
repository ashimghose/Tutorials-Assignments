<?php

namespace App\Notifications;

use App\Models\AcademicOrder;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1, ACA-5: the admin priced the student's quote request.
 */
class QuoteReady extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject("Your quote for {$this->order->reference()}")
            ->line("Our price for {$this->order->service_title}: ".Money::format((int) $this->order->total).($this->order->gst ? ' including GST' : '').'.')
            ->line("The quote is open until {$this->order->quote_expires_at?->format('j M Y')}.")
            ->action('See the quote', route('academic.orders.show', $this->order));
    }
}
