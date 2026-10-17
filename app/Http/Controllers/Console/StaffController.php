<?php

namespace App\Http\Controllers\Console;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Staff accounts are created only by Admin. New staff receive a password-reset link to set
 * their own password, then must enrol in two-factor authentication on first sign-in.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        return view('console.staff', [
            'staff' => User::query()->whereIn('role', Role::staff())->orderBy('name')->get(),
            'roles' => Role::staff(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::staff()))],
        ]);

        $user = new User;
        $user->name = $data['name'];
        $user->email = Str::lower($data['email']);
        $user->role = Role::from($data['role']);
        $user->password = Hash::make(Str::random(48));
        $user->email_verified_at = now();
        $user->save();

        Password::broker()->sendResetLink(['email' => $user->email]);

        activity('staff')->performedOn($user)->causedBy($request->user())->withProperties(['role' => $user->role->value])->log('staff.created');

        return back()->with('success', "{$user->name} added. They've been emailed a link to set their password.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validate([
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::staff()))],
            'is_active' => ['boolean'],
            'reset_two_factor' => ['boolean'],
        ]);

        // Prevent an Admin from locking themselves out.
        if ($user->is($request->user()) && ($data['role'] !== Role::Admin->value || ! $request->boolean('is_active'))) {
            return back()->with('error', 'You cannot demote or deactivate your own account.');
        }

        $before = ['role' => $user->role->value, 'is_active' => $user->is_active];
        $user->role = Role::from($data['role']);
        $user->is_active = $request->boolean('is_active');

        if ($request->boolean('reset_two_factor')) {
            $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);
        }

        $user->save();

        activity('staff')->performedOn($user)->causedBy($request->user())
            ->withProperties(['before' => $before, 'after' => ['role' => $user->role->value, 'is_active' => $user->is_active], 'reset_2fa' => $request->boolean('reset_two_factor')])
            ->log('staff.updated');

        return back()->with('success', "{$user->name} updated.");
    }
}
