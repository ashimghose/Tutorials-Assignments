<?php

namespace App\Models;

use App\Enums\EnrolmentStatus;
use App\Observers\AuditAdminChanges;
use Carbon\CarbonImmutable;
use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * CLS-1: a tuition batch, for example Class 10 Maths (CBSE), with a weekly
 * schedule in India time, a seat limit and a monthly fee in paise.
 *
 * @property int $class_level
 * @property list<array{day: int|string, start: string, minutes: int|string}> $schedule
 * @property int $max_seats
 * @property int $monthly_fee
 * @property bool $record_by_default
 * @property bool $is_active
 * @property Carbon|null $starts_on
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['subject', 'class_level', 'board', 'teacher_id', 'schedule', 'max_seats', 'monthly_fee', 'record_by_default', 'meet_link', 'starts_on', 'is_active'])]
class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $attributes = [
        'max_seats' => 15,
        'is_active' => true,
        'record_by_default' => false,
    ];

    protected function casts(): array
    {
        return [
            'class_level' => 'integer',
            'schedule' => 'array',
            'max_seats' => 'integer',
            'monthly_fee' => 'integer',
            'record_by_default' => 'boolean',
            'is_active' => 'boolean',
            'starts_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return HasMany<ClassSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * @return HasMany<Enrolment, $this>
     */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    /**
     * Open batches a parent may ask to join.
     *
     * @param  Builder<Batch>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function name(): string
    {
        return "Class {$this->class_level} {$this->subject} ({$this->board})";
    }

    public function seatsTaken(): int
    {
        return $this->enrolments()->whereIn('status', EnrolmentStatus::holdingSeat())->count();
    }

    public function hasFreeSeat(): bool
    {
        return $this->seatsTaken() < $this->max_seats;
    }

    /**
     * The schedule in words, for example "Mon, Wed 5:00 pm (60 min)".
     */
    public function scheduleSummary(): string
    {
        return collect($this->schedule)
            ->map(fn (array $slot): string => substr(self::DAYS[(int) $slot['day']] ?? '?', 0, 3).' '
                .CarbonImmutable::createFromFormat('H:i', substr($slot['start'], 0, 5))?->format('g:i a')." ({$slot['minutes']} min)")
            ->implode(', ');
    }

    /**
     * The weekly slots between two dates, as start and end times in UTC.
     *
     * @return list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>
     */
    public function slotsBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $tz = config('app.display_timezone');
        $slots = [];

        for ($day = $from->setTimezone($tz)->startOfDay(); $day->lte($to); $day = $day->addDay()) {
            foreach ($this->schedule as $slot) {
                if ((int) $slot['day'] !== $day->dayOfWeekIso) {
                    continue;
                }
                $start = CarbonImmutable::parse($day->toDateString().' '.$slot['start'], $tz);
                if ($start->lt($from)) {
                    continue;
                }
                $slots[] = ['starts_at' => $start->utc(), 'ends_at' => $start->addMinutes((int) $slot['minutes'])->utc()];
            }
        }

        return $slots;
    }
}
