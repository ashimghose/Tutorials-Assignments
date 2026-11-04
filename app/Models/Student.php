<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A learner. A school student (a minor) is created by their parent (AUTH-3)
 * and holds the minimum child data: first name, class, board and optional
 * school. A music learner (MUS-3) has no parent account: they sign up
 * themselves, and an under-18 learner's parent approves by email.
 *
 * @property Carbon|null $adult_declared_at
 * @property Carbon|null $guardian_consented_at
 */
#[Fillable(['first_name', 'class_level', 'board', 'school'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'class_level' => 'integer',
            'adult_declared_at' => 'datetime',
            'guardian_consented_at' => 'datetime',
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
     * @return BelongsTo<ParentProfile, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * @return HasMany<Enrolment, $this>
     */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    public function hasActiveConsent(): bool
    {
        return $this->consents()->whereNull('withdrawn_at')->exists();
    }

    /**
     * MUS-3: a music learner, who has their own account instead of a parent's.
     */
    public function isMusicLearner(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * MUS-3: an under-18 music learner whose parent hasn't approved yet. They
     * can log in but can't join batches or classes until the parent agrees.
     */
    public function awaitingGuardianConsent(): bool
    {
        return $this->isMusicLearner() && $this->adult_declared_at === null && $this->guardian_consented_at === null;
    }

    public function isMinorMusicLearner(): bool
    {
        return $this->isMusicLearner() && $this->adult_declared_at === null;
    }

    /**
     * Who gets emails about this learner's places and fees: the parent of a
     * school student, or the music learner themselves.
     */
    public function accountHolder(): ?User
    {
        return $this->isMusicLearner() ? $this->user : $this->parent?->user;
    }

    /**
     * The mobile number to reach about fees: the parent's, or the music learner's own.
     */
    public function contactMobile(): ?string
    {
        return $this->isMusicLearner() ? $this->mobile : $this->parent->mobile;
    }

    /**
     * The account holder's page with this learner's classes and fees.
     */
    public function classesRoute(): string
    {
        return $this->isMusicLearner() ? 'music.home' : 'parent.classes';
    }
}
