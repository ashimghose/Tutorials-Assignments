<?php

namespace App\Actions\Notices;

use App\Enums\AccountStatus;
use App\Enums\MarketingAudience;
use App\Enums\Offering;
use App\Enums\Role;
use App\Mail\MarketingMail;
use App\Models\Lead;
use App\Models\MarketingEmail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * NOT-3: sends a marketing email only to people who opted in, each with
 * their own one-click unsubscribe link. Queued, one email per person.
 */
class SendMarketingEmail
{
    public function handle(MarketingEmail $email, User $admin): int
    {
        $sent = 0;
        foreach ($this->recipients($email->audience) as [$address, $unsubscribe]) {
            Mail::to($address)->queue(new MarketingMail($email, $unsubscribe));
            $sent++;
        }

        $email->forceFill(['sent_at' => now(), 'recipients' => $sent, 'created_by' => $email->created_by ?? $admin->id])->save();

        return $sent;
    }

    /**
     * Opted-in addresses with their unsubscribe links, one per address.
     *
     * @return Collection<string, array{0: string, 1: string}>
     */
    public function recipients(MarketingAudience $audience): Collection
    {
        $link = fn (string $type, int $id): string => URL::signedRoute('unsubscribe', ['type' => $type, 'id' => $id]);

        $list = match ($audience) {
            MarketingAudience::Parents, MarketingAudience::AcademicStudents => User::query()
                ->where('role', $audience === MarketingAudience::Parents ? Role::Parent : Role::AcademicStudent)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('marketing_opt_in_at')
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user) => [(string) $user->email, $link('user', $user->id)]),
            MarketingAudience::TuitionLeads, MarketingAudience::AcademicLeads => Lead::query()
                ->where('offering', $audience === MarketingAudience::TuitionLeads ? Offering::Tuition : Offering::Academic)
                ->where('marketing_consent', true)
                ->whereNotNull('email')
                ->latest('id')
                ->get()
                ->map(fn (Lead $lead) => [(string) $lead->email, $link('lead', $lead->id)]),
        };

        return $list->keyBy(fn (array $row) => mb_strtolower($row[0]));
    }
}
