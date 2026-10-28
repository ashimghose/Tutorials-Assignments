<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the business that a site form came in, so it can reply quickly.
 */
class NewLead extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $offering = $this->lead->offering === 'academic' ? 'Academic' : 'Tuition';

        return (new MailMessage)
            ->subject("New {$offering} ".strtolower($this->lead->kind->label()).": {$this->lead->name}")
            ->line("{$this->lead->name} sent a ".strtolower($this->lead->kind->label()).'.')
            ->line('Interest: '.($this->lead->interest ?: '—'))
            ->line('Mobile: '.$this->lead->mobile)
            ->line('Source: '.($this->lead->utm_source ?: 'unknown'))
            ->action('Open in the admin panel', url("/admin/leads/{$this->lead->id}"));
    }
}
