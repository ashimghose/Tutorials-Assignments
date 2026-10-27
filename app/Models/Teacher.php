<?php

namespace App\Models;

use App\Enums\Offering;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A teacher or academic expert, invited by an admin (AUTH-4).
 */
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
}
