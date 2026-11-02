<?php

namespace App\Actions\Accounts;

use App\Enums\Offering;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TeacherInvitation;
use Illuminate\Support\Facades\DB;

/**
 * AUTH-4: an admin invites a teacher or expert by email. The account has no
 * password until the teacher sets one from the invitation link.
 */
class InviteTeacher
{
    /**
     * @param  list<Offering>  $offerings  the sections they work in (VRT-1)
     * @param  array<int, string>  $subjects
     */
    public function __invoke(string $name, string $email, array $offerings, array $subjects = []): User
    {
        $offerings = array_values(array_unique(array_map(fn (Offering $offering) => $offering->value, $offerings)));

        $user = DB::transaction(function () use ($name, $email, $offerings, $subjects) {
            $user = new User(['name' => $name, 'email' => $email]);
            $user->forceFill(['role' => Role::Teacher])->save();
            $user->teacher()->create(['offerings' => $offerings, 'subjects' => $subjects]);

            AuditLog::record('teacher.invited', $user);

            return $user;
        });

        $user->notify(new TeacherInvitation);

        return $user;
    }
}
