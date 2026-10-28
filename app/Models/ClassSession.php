<?php

namespace App\Models;

use App\Enums\ClassSessionStatus;
use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CLS-2: one scheduled class of a batch. Until the own classroom (M10) the
 * class runs on the Google Meet link stored here or on the batch.
 *
 * @property ClassSessionStatus $status
 * @property Carbon|null $scheduled_for
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property bool $recording_on
 */
#[Fillable(['starts_at', 'ends_at', 'recording_on', 'meet_link'])]
class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory;

    /** CLS-2: Join opens this many minutes before the start. */
    public const JOIN_OPENS_MINUTES = 10;

    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClassSessionStatus::class,
            'scheduled_for' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'recording_on' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Classes that haven't finished yet, soonest first.
     *
     * @param  Builder<ClassSession>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('ends_at', '>=', now())->orderBy('starts_at');
    }

    public function isCancelled(): bool
    {
        return $this->status === ClassSessionStatus::Cancelled;
    }

    /**
     * Join is open from 10 minutes before the start until the end.
     */
    public function isJoinOpen(?Carbon $at = null): bool
    {
        $at ??= now();

        return ! $this->isCancelled()
            && $at->gte($this->starts_at->copy()->subMinutes(self::JOIN_OPENS_MINUTES))
            && $at->lte($this->ends_at);
    }

    public function meetLink(): ?string
    {
        return $this->meet_link ?: $this->batch->meet_link;
    }
}
