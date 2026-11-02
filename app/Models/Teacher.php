<?php

namespace App\Models;

use App\Enums\ClassRatePer;
use App\Enums\Offering;
use App\Enums\OrderRateType;
use App\Observers\AuditAdminChanges;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A teacher or academic expert, invited by an admin (AUTH-4).
 *
 * @property list<string> $offerings the sections they work in (VRT-1)
 * @property list<string>|null $subjects
 * @property Carbon|null $active_from
 * @property Carbon|null $active_to
 * @property int|null $class_rate
 * @property ClassRatePer $class_rate_per
 * @property OrderRateType|null $order_rate_type
 * @property int|null $order_rate
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['offerings', 'subjects', 'bio', 'photo_path', 'active_from', 'active_to', 'class_rate', 'class_rate_per', 'order_rate_type', 'order_rate'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'offerings' => 'array',
            'subjects' => 'array',
            'active_from' => 'date',
            'active_to' => 'date',
            'class_rate' => 'integer',
            'class_rate_per' => ClassRatePer::class,
            'order_rate_type' => OrderRateType::class,
            'order_rate' => 'integer',
        ];
    }

    public function worksIn(Offering $offering): bool
    {
        return in_array($offering->value, $this->offerings ?? [], true);
    }

    /**
     * Teachers who work in a section.
     *
     * @param  Builder<Teacher>  $query
     */
    public function scopeWorksInOffering(Builder $query, Offering $offering): void
    {
        $query->whereJsonContains('offerings', $offering->value);
    }

    public function offeringLabels(): string
    {
        return collect($this->offerings ?? [])
            ->map(fn (string $value) => Offering::tryFrom($value)?->label() ?? $value)
            ->implode(', ');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Batch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Academic orders assigned to this expert (ACA-9).
     *
     * @return HasMany<AcademicOrder, $this>
     */
    public function academicOrders(): HasMany
    {
        return $this->hasMany(AcademicOrder::class, 'expert_id');
    }
}
