<?php

namespace App\Notifications;

use App\Actions\Music\SendGuardianConsentRequest;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * MUS-3: asks an under-18 music learner's parent or guardian to approve the
 * learner's account (DPDP Act: a parent consents for a child).
 */
class GuardianConsentRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Student $learner, public string $url) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->learner->user?->name ?? 'Your child';
        $app = config('app.name');

        return (new MailMessage)
            ->subject("Please approve {$name}'s music lessons account")
            ->greeting('Hello')
            ->line("{$name} signed up for music lessons with {$app} and gave your email as their parent or guardian.")
            ->line('Because they are under 18, we need your approval before they can join a class. We keep only their name, email, mobile number and class details.')
            ->action('Review and approve', $this->url)
            ->line('The link works for '.SendGuardianConsentRequest::LINK_DAYS.' days. If you did not expect this email, ignore it and we will delete the account.');
    }
}
