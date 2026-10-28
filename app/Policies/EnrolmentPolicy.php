<?php

namespace App\Policies;

use App\Enums\EnrolmentStatus;
use App\Models\Enrolment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Parents ask for and cancel seats for their own children only; admins
 * confirm fees and remove students. Nobody deletes an enrolment.
 */
class EnrolmentPolicy extends AdminPolicy
{
    /**
     * A parent may ask for a seat for their own child while consent is given.
     */
    public function request(User $user, Student $student): bool
    {
        return $user->parentProfile !== null
            && $student->parent_id === $user->parentProfile->id
            && $student->hasActiveConsent();
    }

    /**
     * A parent may cancel a request that is still waiting for the fee.
     */
    public function cancel(User $user, Enrolment $enrolment): bool
    {
        return $user->parentProfile !== null
            && $enrolment->student->parent_id === $user->parentProfile->id
            && $enrolment->status === EnrolmentStatus::Pending;
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
