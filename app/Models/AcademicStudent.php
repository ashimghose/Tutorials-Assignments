<?php

namespace App\Models;

use Database\Factories\AcademicStudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An adult who orders academic services (ACA-3). The 18+ declaration time
 * is stored as proof.
 */
#[Fillable(['mobile', 'university', 'course', 'level', 'stream'])]
class AcademicStudent extends Model
{
    /** @use HasFactory<AcademicStudentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'adult_declared_at' => 'datetime',
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
     * @return HasMany<AcademicOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(AcademicOrder::class);
    }
}
