<?php

namespace App\Notifications;

use App\Models\AcademicOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOT-1, ACA-7: the order moved to a status the admin set to notify the student.
 */
class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AcademicOrder $order, public ?string $note = null) {}

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
            ->subject("Order {$this->order->reference()}: {$this->order->status->name}")
            ->line("Your order for {$this->order->service_title} is now: {$this->order->status->name}.");

        if ($this->note) {
            $message->line($this->note);
        }
        if ($this->order->status->needs_tracking && $this->order->tracking_number) {
            $message->line("Courier: {$this->order->courier_name}, tracking number {$this->order->tracking_number}.");
        }

        return $message->action('Track your order', route('academic.orders.show', $this->order));
    }
}
