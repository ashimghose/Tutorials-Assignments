<?php

namespace App\Actions\Chat;

use App\Jobs\EmailUnreadMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\OfficeHours;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CHAT-3, CHAT-5: adds a message (and attachment) to a conversation. Outside
 * office hours the conversation gets one automatic "we'll reply by" note.
 * Members who haven't read it in 15 minutes are emailed (NOT-1). The caller
 * authorizes and validates.
 */
class PostMessage
{
    /**
     * CHAT-3: attachments are images, PDF or Word files up to 10 MB, checked from their content.
     *
     * @return array<int, string>
     */
    public static function attachmentRules(): array
    {
        return ['nullable', 'file', 'mimetypes:'.implode(',', config('chat.attachment_mimes')), 'max:'.config('chat.attachment_max_kb')];
    }

    /**
     * Messages are rate-limited per person.
     *
     * @throws ValidationException
     */
    public static function ensureNotTooFast(User $user, string $field = 'body'): void
    {
        $key = 'chat:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, (int) config('chat.per_minute'))) {
            throw ValidationException::withMessages([$field => __('You are sending messages too quickly. Please wait a minute.')]);
        }

        RateLimiter::hit($key);
    }

    public function handle(Conversation $conversation, User $sender, string $body, ?UploadedFile $attachment = null): Message
    {
        $message = $conversation->messages()->make(['body' => $body]);
        $message->forceFill(['sender_id' => $sender->id]);

        if ($attachment !== null) {
            $disk = (string) config('academic.files_disk');
            $message->forceFill([
                'attachment_disk' => $disk,
                'attachment_path' => $attachment->storeAs("chat/{$conversation->id}", Str::uuid().'.'.($attachment->guessExtension() ?: 'bin'), ['disk' => $disk]),
                'attachment_name' => Str::limit($attachment->getClientOriginalName(), 200, ''),
                'attachment_mime' => (string) $attachment->getMimeType(),
                'attachment_size' => (int) $attachment->getSize(),
            ]);
        }

        $message->save();
        $conversation->forceFill(['last_message_at' => $message->created_at])->save();
        $conversation->markReadBy($sender);

        if (! $sender->isAdmin() && $sender->teacher === null && ! OfficeHours::isOpen()) {
            $this->replyOutsideHours($conversation);
        }

        EmailUnreadMessage::dispatch($message)->delay(now()->addMinutes((int) config('chat.unread_email_after_minutes')));

        return $message;
    }

    private function replyOutsideHours(Conversation $conversation): void
    {
        $opens = OfficeHours::nextOpening();
        $alreadySaid = $conversation->messages()->whereNull('sender_id')->where('created_at', '>=', now()->subHours(16))->exists();
        if ($alreadySaid) {
            return;
        }

        $conversation->messages()->make(['body' => __('Thanks for your message. Our office is closed now (:hours). We will reply by :time.', [
            'hours' => OfficeHours::summary(),
            'time' => $opens->format('l, g:i a'),
        ])])->save();
    }
}
