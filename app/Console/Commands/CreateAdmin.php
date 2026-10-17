<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('josveggies:create-admin')]
#[Description('Create the first Admin account (use once on a fresh server)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $name = text('Name', required: true);
        $email = text('Email', required: true, validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email.');
        $plain = password('Password (min 10 characters)', required: true);

        $validator = Validator::make(['email' => $email, 'password' => $plain], [
            'email' => ['unique:users,email'],
            'password' => [Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = strtolower($email);
        $user->password = Hash::make($plain);
        $user->role = Role::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Admin {$user->email} created. Sign in at /console/login and set up two-factor authentication.");

        return self::SUCCESS;
    }
}
