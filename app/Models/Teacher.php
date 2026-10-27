<?php

namespace App\Models;

use App\Enums\Offering;
use App\Observers\AuditAdminChanges;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A teacher or academic expert, invited by an admin (AUTH-4).
 *
 * @property Offering $offering
 * @property list<string>|null $subjects
 * @property Carbon|null $active_from
 * @property Carbon|null $active_to
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['offering', 'subjects', 'bio', 'photo_path', 'active_from', 'active_to'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'offering' => Offering::class,
            'subjects' => 'array',
            'active_from' => 'date',
            'active_to' => 'date',
        ];
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
}
