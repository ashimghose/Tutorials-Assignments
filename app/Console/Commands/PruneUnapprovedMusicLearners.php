<?php

namespace App\Console\Commands;

use App\Actions\Music\SendGuardianConsentRequest;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * MUS-3, privacy (DPDP Act), run daily: deletes under-18 music learners'
 * accounts that no parent approved before the email link expired.
 */
#[Signature('app:prune-unapproved-music-learners')]
#[Description('Delete under-18 music accounts a parent did not approve in time')]
class PruneUnapprovedMusicLearners extends Command
{
    public function handle(): int
    {
        $deleted = 0;
        User::query()
            ->where('role', Role::MusicStudent)
            ->where('created_at', '<', now()->subDays(SendGuardianConsentRequest::LINK_DAYS))
            ->whereHas('student', fn (Builder $query) => $query->whereNull('adult_declared_at')->whereNull('guardian_consented_at'))
            ->each(function (User $user) use (&$deleted) {
                $user->delete();
                $deleted++;
            });

        $this->info("Accounts deleted: {$deleted}.");

        return self::SUCCESS;
    }
}
