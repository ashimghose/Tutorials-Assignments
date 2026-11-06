<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Support\Phone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIR-3: tells the business that someone applied for a job.
 */
class NewJobApplication extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobApplication $application) {}

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
            ->subject("New job application: {$this->application->job_title}")
            ->line("{$this->application->name} from {$this->application->city} applied for {$this->application->job_title}.")
            ->line('Mobile: '.Phone::display($this->application->mobile))
            ->line('Source: '.($this->application->utm_source ?: 'unknown'))
            ->action('Open in the admin panel', url("/admin/job-applications/{$this->application->id}"));
    }
}
