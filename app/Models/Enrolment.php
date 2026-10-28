<?php

namespace App\Models;

use App\Enums\EnrolmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EnrolmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A child's place in a batch. It becomes Active only when the fee is
 * confirmed (PAY-2); access lasts until the paid-through date plus a 3-day
 * grace period (PAY-4). Status and dates are never mass-assignable.
 *
 * @property EnrolmentStatus $status
 * @property Carbon|null $paid_through
 * @property Carbon|null $removed_at
 * @property Carbon|null $last_reminder_on
 */
class Enrolment extends Model
{
    /** @use HasFactory<EnrolmentFactory> */
    use HasFactory;

    /** PAY-4: days of access after the paid-through date. */
    public const GRACE_DAYS = 3;

    /** PAY-4: reminder emails this many days before the paid-through date. */
    public const REMINDER_DAYS = [5, 1];

    protected $fillable = [];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrolmentStatus::class,
            'paid_through' => 'date',
            'removed_at' => 'datetime',
            'last_reminder_on' => 'date',
        ];
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
     * Today's date in India, where fees and access are counted.
     */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::today(config('app.display_timezone'));
    }

    /**
     * CLS-3: enrolments whose child may join classes today.
     *
     * @param  Builder<Enrolment>  $query
     */
    public function scopeWithAccess(Builder $query): void
    {
        $query->where('status', EnrolmentStatus::Active)
            ->whereDate('paid_through', '>=', self::today()->subDays(self::GRACE_DAYS)->toDateString());
    }

    public function hasAccess(): bool
    {
        return $this->status === EnrolmentStatus::Active
            && $this->paid_through !== null
            && $this->paid_through->toDateString() >= self::today()->subDays(self::GRACE_DAYS)->toDateString();
    }

    /**
     * In the grace period: the paid-through date has passed but access continues.
     */
    public function inGracePeriod(): bool
    {
        return $this->hasAccess() && $this->paid_through?->toDateString() < self::today()->toDateString();
    }
}
