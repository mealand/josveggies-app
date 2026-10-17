<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Customer self-registration. Staff accounts are only ever created by an Admin.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Phone::normalize((string) $value) === null) {
                    $fail('Enter a valid Nigerian mobile number, e.g. 0803 123 4567.');
                }
            }],
            'password' => $this->passwordRules(),
            'privacy' => ['accepted'],
        ], [
            'privacy.accepted' => 'Please accept the privacy policy to create an account.',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => Str::lower($input['email']),
            'phone' => Phone::normalize($input['phone']),
            'password' => Hash::make($input['password']),
        ]);
    }
}
