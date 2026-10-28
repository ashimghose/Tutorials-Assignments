<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AcademicStudent;
use App\Models\ParentProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Set while a profile factory makes its own user, so the user doesn't
     * get a second profile.
     */
    protected static bool $withoutProfile = false;

    /**
     * Make a user without the profile configure() would add.
     */
    public static function makeUserWithoutProfile(self $factory): User
    {
        static::$withoutProfile = true;

        try {
            return $factory->createOne();
        } finally {
            static::$withoutProfile = false;
        }
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => Role::Parent,
            'status' => AccountStatus::Active,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Give each role the profile it needs. Parents are the default.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (static::$withoutProfile) {
                return;
            }

            match ($user->role) {
                Role::Parent => $user->parentProfile()->exists() || ParentProfile::factory()->for($user)->create(),
                Role::AcademicStudent => $user->academicStudent()->exists() || AcademicStudent::factory()->for($user)->create(),
                Role::Teacher => $user->teacher()->exists() || Teacher::factory()->for($user)->create(),
                default => null,
            };
        });
    }

    public function parent(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Parent]);
    }

    public function academicStudent(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::AcademicStudent]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Teacher]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Admin]);
    }

    /**
     * A school student's login: a username and no email. Pass the child
     * record with ->for() on Student instead when the parent matters.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Student,
            'email' => null,
            'email_verified_at' => null,
            'username' => fake()->unique()->userName(),
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AccountStatus::Deactivated]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
