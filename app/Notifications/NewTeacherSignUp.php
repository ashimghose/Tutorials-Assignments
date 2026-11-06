<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIR-8: tells the business that a teacher or expert signed up and is
 * waiting for approval.
 */
class NewTeacherSignUp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $teacher) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $profile = $this->teacher->teacher;

        return (new MailMessage)
            ->subject("New teacher sign-up: {$this->teacher->name}")
            ->line("{$this->teacher->name} signed up as a teacher or expert and is waiting for your approval.")
            ->line('Works in: '.($profile?->offeringLabels() ?: '—'))
            ->line('Teaches: '.implode(', ', $profile->subjects ?? []))
            ->line('Mobile: '.Phone::display($profile?->mobile))
            ->line('Approve them only after the checks: ID, references and, for work with children, police verification.')
            ->action('Open in the admin panel', url('/admin/teachers?activeTab=pending'));
    }
}
