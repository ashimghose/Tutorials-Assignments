<?php

namespace App\Actions\Music;

use App\Models\Student;
use App\Notifications\GuardianConsentRequest;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * MUS-3: emails an under-18 music learner's parent a signed link to approve
 * the account. The parent has no login; the link is the proof it reached them.
 */
class SendGuardianConsentRequest
{
    /** Days the approval link works; unapproved accounts are deleted after this. */
    public const LINK_DAYS = 14;

    public function handle(Student $learner): void
    {
        $url = URL::temporarySignedRoute('music.consent.show', now()->addDays(self::LINK_DAYS), ['student' => $learner->id]);

        Notification::route('mail', $learner->guardian_email)
            ->notify(new GuardianConsentRequest($learner, $url));
    }
}
