<?php

namespace App\Models;

use App\Enums\JobApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * HIR-2, HIR-4: someone's application for a job on the Careers pages.
 * Personal data: never log it, and delete it after a year (HIR-5).
 *
 * @property JobApplicationStatus $status
 * @property list<string> $languages
 * @property Carbon $declared_at
 */
#[Fillable(['name', 'mobile', 'email', 'city', 'languages', 'subjects', 'qualifications', 'experience', 'availability', 'link', 'message'])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    use MassPrunable;

    /** HIR-5: months an application is kept. */
    public const KEEP_MONTHS = 12;

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'declared_at' => 'datetime',
            'status' => JobApplicationStatus::class,
        ];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }

    /**
     * @return BelongsTo<JobOpening, $this>
     */
    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }
}
