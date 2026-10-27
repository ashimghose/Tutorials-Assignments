<?php

namespace App\Notifications;

use App\Models\AcademicOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the admins about a new order or quote request.
 */
class NewAcademicOrder extends Notification implements ShouldQueue
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
        $what = $this->order->is_quote ? 'Quote request' : 'New order';

        return (new MailMessage)
            ->subject("{$what} {$this->order->reference()}: {$this->order->service_title}")
            ->line("{$what} for {$this->order->service_title} ({$this->order->level->label()}), due {$this->order->deadline->format('j M Y')}.")
            ->line($this->order->is_quote ? 'Send the student a quote from the admin panel.' : 'Send the payment link, then mark the payment as received.')
            ->action('Open the order', url("/admin/academic-orders/{$this->order->id}"));
    }
}
