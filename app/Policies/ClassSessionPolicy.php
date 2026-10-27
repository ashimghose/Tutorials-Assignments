<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * CLS-2, CLS-3: who may join a class, checked on the server each time.
 * The teacher of the batch, an admin, or a student whose enrolment gives
 * access today; only from 10 minutes before the start until the end.
 */
class ClassSessionPolicy
{
    public function join(User $user, ClassSession $session): Response
    {
        if ($session->isCancelled()) {
            return Response::deny(__('This class is cancelled.'));
        }

        if (! $session->isJoinOpen()) {
            return Response::deny(__('Join opens :minutes minutes before the class starts.', ['minutes' => ClassSession::JOIN_OPENS_MINUTES]));
        }

        if ($user->isAdmin() || ($session->batch->teacher !== null && $session->batch->teacher->user_id === $user->id)) {
            return Response::allow();
        }

        $student = $user->isStudent() ? $user->student : null;

        return $student !== null && Enrolment::query()->whereBelongsTo($student)->whereBelongsTo($session->batch)->withAccess()->exists()
            ? Response::allow()
            : Response::deny(__('Only students with an active enrolment can join this class.'));
    }
}
