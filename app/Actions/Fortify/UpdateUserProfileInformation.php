<?php

namespace App\Actions\Fortify;

use App\Models\DispatchZone;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Phone::normalize((string) $value) === null) {
                    $fail('Enter a valid Nigerian mobile number.');
                }
            }],
            'default_address' => ['nullable', 'string', 'max:500'],
            'default_dispatch_zone_id' => ['nullable', 'integer', Rule::exists(DispatchZone::class, 'id')],
        ])->validateWithBag('updateProfileInformation');

        $attributes = [
            'name' => $input['name'],
            'email' => Str::lower($input['email']),
            'phone' => Phone::normalize($input['phone']),
            'default_address' => $input['default_address'] ?? null,
            'default_dispatch_zone_id' => $input['default_dispatch_zone_id'] ?? null,
        ];

        if ($attributes['email'] !== $user->email) {
            $user->forceFill($attributes + ['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();

            return;
        }

        $user->forceFill($attributes)->save();
    }
}
