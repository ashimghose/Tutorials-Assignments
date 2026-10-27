<?php

namespace App\Models;

use App\Enums\EnrolmentStatus;
use App\Enums\NoticeAudience;
use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * NOT-2: a notice from the admin to all parents, the parents of one batch,
 * or all academic students. Shown on their pages and emailed.
 *
 * @property NoticeAudience $audience
 * @property Carbon|null $published_at
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['audience', 'batch_id', 'title', 'body'])]
class Notice extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Notice $notice) {
            if ($notice->audience !== NoticeAudience::Batch) {
                $notice->batch_id = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'audience' => NoticeAudience::class,
            'published_at' => 'datetime',
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
     * Published notices this person should see, newest first.
     *
     * @param  Builder<Notice>  $query
     */
    public function scopeFor(Builder $query, User $user): void
    {
        $query->whereNotNull('published_at')->where('published_at', '>=', now()->subDays(60))->latest('published_at');

        if ($user->academicStudent !== null) {
            $query->where('audience', NoticeAudience::AcademicStudents);

            return;
        }

        if ($user->parentProfile === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $batchIds = Enrolment::query()
            ->whereIn('student_id', $user->parentProfile->students()->select('id'))
            ->where('status', '!=', EnrolmentStatus::Removed)
            ->pluck('batch_id');

        $query->where(fn (Builder $query) => $query
            ->where('audience', NoticeAudience::Parents)
            ->orWhere(fn (Builder $query) => $query->where('audience', NoticeAudience::Batch)->whereIn('batch_id', $batchIds)));
    }

    /**
     * The people the notice is for.
     *
     * @return Collection<int, User>
     */
    public function recipients(): Collection
    {
        return match ($this->audience) {
            NoticeAudience::Parents => User::query()->whereHas('parentProfile')->where('status', 'active')->get(),
            NoticeAudience::AcademicStudents => User::query()->whereHas('academicStudent')->where('status', 'active')->get(),
            NoticeAudience::Batch => User::query()->where('status', 'active')->whereHas('parentProfile.students.enrolments', fn (Builder $query) => $query
                ->where('batch_id', $this->batch_id)->where('status', '!=', EnrolmentStatus::Removed))->get(),
        };
    }
}
