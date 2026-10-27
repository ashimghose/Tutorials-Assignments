<?php

namespace App\Models;

use App\Enums\Offering;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * CHAT-1: a conversation tagged Tuition or Academic, maybe about a batch or
 * an order. Members are the people it belongs to; a school student's parent
 * is always a member (CHAT-4). Staff see it through the admin inbox, by
 * assignment, or as the batch's teacher.
 *
 * @property Offering $offering
 * @property Carbon|null $closed_at
 * @property Carbon|null $last_message_at
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $fillable = ['subject'];

    protected function casts(): array
    {
        return [
            'offering' => Offering::class,
            'closed_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Everyone with a read position: members and staff readers.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_member', 'last_read_at', 'notified_at')->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('is_member', true);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return BelongsTo<AcademicOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(AcademicOrder::class, 'academic_order_id');
    }

    /**
     * CHAT-2: the conversations a person may see. Admins see all; teachers
     * and experts those assigned to them or about their batches; everyone
     * else those they are a member of.
     *
     * @param  Builder<Conversation>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->where(function (Builder $query) use ($user) {
            $query->whereHas('users', fn (Builder $q) => $q->whereKey($user->id)->where('conversation_user.is_member', true));

            if ($user->teacher !== null) {
                $query->orWhere('assigned_to', $user->id)
                    ->orWhereIn('batch_id', Batch::query()->select('id')->whereBelongsTo($user->teacher));
            }
        });
    }

    /**
     * CHAT-2: adds unread_count, the messages from others this person hasn't read.
     *
     * @param  Builder<Conversation>  $query
     */
    public function scopeWithUnreadFor(Builder $query, User $user): void
    {
        $query->withCount(['messages as unread_count' => fn (Builder $messages) => $messages
            ->where(fn (Builder $q) => $q->whereNull('messages.sender_id')->orWhere('messages.sender_id', '!=', $user->id))
            ->whereNotExists(fn ($read) => $read->from('conversation_user')
                ->whereColumn('conversation_user.conversation_id', 'messages.conversation_id')
                ->where('conversation_user.user_id', $user->id)
                ->whereColumn('conversation_user.last_read_at', '>=', 'messages.created_at'))]);
    }

    /**
     * Whether anyone other than the sender has read up to this message (CHAT-3 read receipt).
     */
    public function isSeen(Message $message): bool
    {
        return $this->users()
            ->where('users.id', '!=', $message->sender_id)
            ->wherePivot('last_read_at', '>=', $message->created_at)
            ->exists();
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * When this person last read the conversation, if ever.
     */
    public function lastReadBy(User $user): ?Carbon
    {
        $pivot = $this->users()->whereKey($user->id)->first()?->getRelation('pivot');
        $read = $pivot?->getAttribute('last_read_at');

        return $read ? Carbon::parse($read) : null;
    }

    public function markReadBy(User $user): void
    {
        if ($this->users()->whereKey($user->id)->exists()) {
            $this->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        } else {
            // A staff reader gets a read position without becoming a member.
            $this->users()->attach($user->id, ['is_member' => false, 'last_read_at' => now()]);
        }
    }
}
