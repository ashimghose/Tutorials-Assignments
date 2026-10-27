<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * One login table for everyone; `role` decides access (AUTH-5). Role,
 * status and lockout fields are never mass-assignable.
 *
 * @property Role|null $role
 * @property AccountStatus $status
 * @property Carbon|null $locked_until
 * @property Carbon|null $last_login_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * AUTH-6: this many failed logins in a row lock the account.
     */
    public const MAX_FAILED_LOGINS = 5;

    public const LOCKOUT_MINUTES = 15;

    /**
     * Matches the column default, so a new model is active before it is
     * reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'failed_login_attempts' => 0,
    ];

    protected static function booted(): void
    {
        // A parent's children's logins go with the family account.
        static::deleting(function (User $user) {
            $user->parentProfile?->students()->with('user')->get()
                ->each(fn (Student $student) => $student->user?->delete());
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => AccountStatus::class,
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(Role::Student);
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * School students log in with a username their parent chose and have no
     * email to verify; their parent's email is verified instead.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->role === Role::Student || $this->email_verified_at !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && $this->isActive();
    }

    public function homeRoute(): string
    {
        return $this->role?->homeRoute() ?? 'home';
    }

    /**
     * @return HasOne<ParentProfile, $this>
     */
    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    /**
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * @return HasOne<AcademicStudent, $this>
     */
    public function academicStudent(): HasOne
    {
        return $this->hasOne(AcademicStudent::class);
    }

    /**
     * @return HasOne<Teacher, $this>
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }
}
