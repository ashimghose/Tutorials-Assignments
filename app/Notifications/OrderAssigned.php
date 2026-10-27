<?php

namespace App\Notifications;

use App\Models\AcademicOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ACA-9: tells an expert an order was assigned to them.
 */
class OrderAssigned extends Notification implements ShouldQueue
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
            ->subject("Order {$this->order->reference()} assigned to you")
            ->line("{$this->order->service_title} ({$this->order->level->label()}), due {$this->order->deadline->format('j M Y')}.")
            ->action('Open the order', route('teacher.orders.show', $this->order));
    }
}
