<x-layouts.console title="Staff">
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                    <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">2FA</th><th class="px-5 py-3">Last sign-in</th><th class="px-5 py-3">Role & access</th></tr>
                </thead>
                <tbody class="divide-y divide-leaf-900/5">
                    @foreach ($staff as $member)
                        <tr class="{{ $member->is_active ? '' : 'opacity-60' }}">
                            <td class="px-5 py-3"><p class="font-semibold">{{ $member->name }}</p><p class="text-xs text-leaf-900/55">{{ $member->email }}</p></td>
                            <td class="px-5 py-3">{!! $member->hasConfirmedTwoFactor() ? '<span class="chip bg-leaf-100 text-leaf-800">On</span>' : '<span class="chip bg-tomato-500/10 text-tomato-600">Not set</span>' !!}</td>
                            <td class="px-5 py-3 text-leaf-900/60">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('console.staff.update', $member) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="input w-36 py-1.5">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($member->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($member->is_active) class="rounded text-leaf-600"> Active</label>
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="reset_two_factor" value="1" class="rounded text-tomato-500"> Reset 2FA</label>
                                    <button class="btn-ghost btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('console.staff.store') }}" class="card h-fit space-y-4 p-6">
            @csrf
            <h2 class="font-sans text-lg font-bold">Add a staff member</h2>
            <x-field name="name" label="Full name" required />
            <x-field name="email" label="Work email" type="email" required />
            <div>
                <label for="role" class="label">Role</label>
                <select id="role" name="role" class="input">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-leaf-900/55">They'll receive an email to set a password, then must enable two-factor authentication. Only Admin and Jos Ops can see market days.</p>
            <button class="btn-primary w-full">Create account</button>
        </form>
    </div>
</x-layouts.console>
