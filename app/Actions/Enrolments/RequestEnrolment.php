<?php

namespace App\Actions\Enrolments;

use App\Enums\EnrolmentStatus;
use App\Models\Batch;
use App\Models\Enrolment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\EnrolmentRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * A parent asks for a seat for their child. The seat is held as "Waiting
 * for fee" until the fee is confirmed (PAY-2). The caller authorizes.
 */
class RequestEnrolment
{
    public function handle(Student $student, Batch $batch): Enrolment
    {
        if (! $batch->is_active) {
            throw ValidationException::withMessages(['batch' => __('This batch is not taking new students.')]);
        }

        $enrolment = DB::transaction(function () use ($student, $batch) {
            // Lock the batch row so two parents can't take the last seat at once.
            Batch::query()->whereKey($batch->id)->lockForUpdate()->first();

            $enrolment = Enrolment::query()->whereBelongsTo($student)->whereBelongsTo($batch)->first()
                ?? (new Enrolment)->forceFill(['student_id' => $student->id, 'batch_id' => $batch->id]);

            if ($enrolment->exists && in_array($enrolment->status, [...EnrolmentStatus::holdingSeat(), EnrolmentStatus::Removed], true)) {
                throw ValidationException::withMessages(['batch' => $enrolment->status === EnrolmentStatus::Removed
                    ? __(':name was removed from this batch. Please message us to rejoin.', ['name' => $student->first_name])
                    : __(':name already has a place in this batch.', ['name' => $student->first_name])]);
            }

            if (! $batch->hasFreeSeat()) {
                throw ValidationException::withMessages(['batch' => __('Sorry, this batch is full.')]);
            }

            $enrolment->forceFill(['status' => EnrolmentStatus::Pending])->save();

            return $enrolment;
        });

        Notification::send(User::query()->admins()->get(), new EnrolmentRequested($enrolment));

        return $enrolment;
    }
}
