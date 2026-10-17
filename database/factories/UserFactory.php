<?php

namespace Database\Factories;

use App\Enums\Role;
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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '23480'.fake()->numerify('########'),
            'remember_token' => Str::random(10),
        ];
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

    public function role(Role $role): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = $role);
    }

    public function admin(): static
    {
        return $this->role(Role::Admin)->withTwoFactor();
    }

    public function josOps(): static
    {
        return $this->role(Role::JosOps)->withTwoFactor();
    }

    public function abujaOps(): static
    {
        return $this->role(Role::AbujaOps)->withTwoFactor();
    }

    /**
     * Confirmed two-factor authentication (required for staff to use the console).
     */
    public function withTwoFactor(): static
    {
        return $this->afterMaking(function (User $user): void {
            $user->forceFill([
                'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
                'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-one', 'recovery-code-two'])),
                'two_factor_confirmed_at' => now(),
            ]);
        });
    }
}
