<?php

namespace App\Policies;

use App\Models\AcademicOrder;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Academic students see only their own orders; experts only the orders
 * assigned to them (ACA-9); admins all. Nobody deletes an order.
 */
class AcademicOrderPolicy extends AdminPolicy
{
    public function view(User $user, Model $order): bool
    {
        return $order instanceof AcademicOrder && ($this->owns($user, $order) || $this->isExpertOn($user, $order) || $user->isAdmin());
    }

    public function create(User $user): bool
    {
        return $user->academicStudent !== null && $user->hasVerifiedEmail();
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    /**
     * ACA-8: the student asks for a free revision after a draft or final.
     */
    public function requestRevision(User $user, AcademicOrder $order): bool
    {
        return $this->owns($user, $order)
            && $order->isPaid()
            && ! $order->status->is_final
            && ! $order->status->is_revision
            && $order->revisionsLeft() > 0
            && $order->files()->where('kind', '!=', 'brief')->exists();
    }

    /**
     * ACA-9: the assigned expert uploads drafts and finals; the student adds
     * more brief files while the order is open; admins always.
     */
    public function upload(User $user, AcademicOrder $order): bool
    {
        if ($order->status->is_final) {
            return false;
        }

        return $this->owns($user, $order) || $this->isExpertOn($user, $order) || $user->isAdmin();
    }

    /**
     * ACA-9: the expert moves a paid order only to statuses the admin allows
     * experts to set.
     */
    public function setStatus(User $user, AcademicOrder $order, OrderStatus $to): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->isExpertOn($user, $order) && $order->isPaid() && ! $order->status->is_final && $to->expert_can_set;
    }

    private function owns(User $user, AcademicOrder $order): bool
    {
        return $user->academicStudent !== null && $order->academic_student_id === $user->academicStudent->id;
    }

    private function isExpertOn(User $user, AcademicOrder $order): bool
    {
        return $user->teacher !== null && $order->expert_id === $user->teacher->id;
    }
}
