<?php

namespace App\Actions\Chat;

use App\Enums\Offering;
use App\Models\AcademicOrder;
use App\Models\Batch;
use App\Models\Conversation;
use App\Models\Student;
use App\Models\User;
use App\Notifications\NewConversation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * CHAT-1, CHAT-4: starts a conversation. When a school student is involved
 * (as the sender or the subject) their parent is always a member. An order
 * conversation is assigned to the order's expert. The caller authorizes.
 */
class StartConversation
{
    public function __construct(private PostMessage $post) {}

    public function handle(User $starter, Offering $offering, string $subject, string $body, ?Student $student = null, ?Batch $batch = null, ?AcademicOrder $order = null, ?UploadedFile $attachment = null): Conversation
    {
        if ($starter->isStudent()) {
            $student = $starter->student;
        }

        $conversation = DB::transaction(function () use ($starter, $offering, $subject, $student, $batch, $order) {
            $conversation = new Conversation(['subject' => $subject]);
            $conversation->forceFill([
                'offering' => $offering,
                'started_by' => $starter->id,
                'student_id' => $student?->id,
                'batch_id' => $batch?->id,
                'academic_order_id' => $order?->id,
                'assigned_to' => $order?->expert?->user_id,
            ])->save();

            $members = [$starter->id];
            if ($student !== null) {
                $members[] = $student->parent->user_id;
            }
            $conversation->users()->attach(array_fill_keys(array_unique($members), ['is_member' => true]));

            return $conversation;
        });

        $this->post->handle($conversation, $starter, $body, $attachment);

        Notification::send(User::query()->admins()->get(), new NewConversation($conversation));

        return $conversation;
    }
}
