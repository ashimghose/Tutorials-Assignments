<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use App\Support\Verticals;
use Database\Factories\JobOpeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * HIR-1: a job on the Careers pages, written by the admin. The body is
 * Markdown.
 *
 * @property bool $is_open
 * @property int $openings
 * @property Carbon $posted_on
 * @property Carbon|null $closes_on
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['slug', 'offering', 'title', 'summary', 'body', 'location', 'region', 'languages', 'pay', 'openings', 'is_open', 'posted_on', 'closes_on', 'sort_order'])]
class JobOpening extends Model
{
    /** @use HasFactory<JobOpeningFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'openings' => 'integer',
            'posted_on' => 'date',
            'closes_on' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Jobs people can apply for today (India time): open, posted, not past
     * their closing date, and in a section that is switched on (VRT-1).
     *
     * @param  Builder<self>  $query
     */
    public function scopeAcceptingApplications(Builder $query): void
    {
        $today = now(config('app.display_timezone'))->toDateString();

        $query->where('is_open', true)
            ->whereIn('offering', Verticals::enabledKeys())
            ->whereDate('posted_on', '<=', $today)
            ->where(fn (Builder $query) => $query->whereNull('closes_on')->orWhereDate('closes_on', '>=', $today));
    }

    public function isAcceptingApplications(): bool
    {
        return self::query()->whereKey($this->getKey())->acceptingApplications()->exists();
    }

    public function bodyHtml(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
