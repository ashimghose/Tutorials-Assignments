<?php

namespace App\Policies;

use App\Enums\EnrolmentStatus;
use App\Enums\Role;
use App\Models\Enrolment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Parents ask for and cancel seats for their own children only, and music
 * learners for themselves; admins
 * confirm fees and remove students. Nobody deletes an enrolment.
 */
class EnrolmentPolicy extends AdminPolicy
{
    /**
     * A parent may ask for a seat for their own child while consent is given.
     */
    public function request(User $user, Student $student): bool
    {
        return $this->holdsAccount($user, $student)
            && $student->hasActiveConsent()
            && ! $student->awaitingGuardianConsent();
    }

    /**
     * A parent may cancel a request that is still waiting for the fee.
     */
    public function cancel(User $user, Enrolment $enrolment): bool
    {
        return $this->holdsAccount($user, $enrolment->student)
            && $enrolment->status === EnrolmentStatus::Pending;
    }

    /**
     * The parent of a school student, or a music learner for themselves (MUS-3).
     */
    private function holdsAccount(User $user, Student $student): bool
    {
        if ($student->isMusicLearner()) {
            return $user->hasRole(Role::MusicStudent) && $student->user_id === $user->id;
        }

        return $user->parentProfile !== null && $student->parent_id === $user->parentProfile->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
