<?php

namespace App\Models;

use Database\Factories\ParentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A parent's family account ("parents" table). Named ParentProfile because
 * "parent" is a reserved word in PHP.
 */
#[Table('parents')]
#[Fillable(['mobile'])]
class ParentProfile extends Model
{
    /** @use HasFactory<ParentProfileFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
