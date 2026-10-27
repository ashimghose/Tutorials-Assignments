<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the first admin, who then reaches /admin. Admins can't sign up
 * through the website.
 */
#[Signature('app:make-admin')]
#[Description('Create an admin account for the /admin panel')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $name = text('Name', required: true);
        $email = text('Email address', required: true, validate: ['email' => ['required', 'email', 'unique:users,email']]);
        $secret = password('Password', required: true, validate: ['password' => ['required', Password::default()]]);

        $user = new User(['name' => $name, 'email' => $email, 'password' => $secret]);
        $user->forceFill(['role' => Role::Admin, 'email_verified_at' => now()])->save();

        $this->info('Admin created. Log in at '.url('/login')." with {$email}.");

        return self::SUCCESS;
    }
}
