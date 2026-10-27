<?php

namespace App\Models;

use App\Enums\ConsentPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proof of consent: who agreed, to what, which policy version and when.
 * Written only by the app (never from a form), so nothing is fillable.
 */
class Consent extends Model
{
    protected function casts(): array
    {
        return [
            'purpose' => ConsentPurpose::class,
            'given_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public static function record(User $user, ConsentPurpose $purpose, ?Student $student = null): self
    {
        $consent = new self;
        $consent->forceFill([
            'user_id' => $user->id,
            'student_id' => $student?->id,
            'purpose' => $purpose,
            'policy_version' => config('privacy.policy_version'),
            'given_at' => now(),
        ])->save();

        return $consent;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
