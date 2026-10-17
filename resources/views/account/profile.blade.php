@php $user = auth()->user(); @endphp

<x-layouts.account title="Profile & security">
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="card space-y-4 p-6">
            @csrf
            @method('PUT')
            <h2 class="font-sans text-lg font-bold text-leaf-900">Your details</h2>
            <div>
                <label class="label" for="name">Full name</label>
                <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
            </div>
            <div>
                <label class="label" for="phone">Phone</label>
                <input class="input" id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required autocomplete="tel">
            </div>
            <div>
                <label class="label" for="default_dispatch_zone_id">Default delivery zone</label>
                <select class="input" id="default_dispatch_zone_id" name="default_dispatch_zone_id">
                    <option value="">None</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" @selected(old('default_dispatch_zone_id', $user->default_dispatch_zone_id) == $zone->id)>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="default_address">Default delivery address</label>
                <textarea class="input" id="default_address" name="default_address" rows="3">{{ old('default_address', $user->default_address) }}</textarea>
            </div>
            @foreach ($errors->updateProfileInformation->all() as $error)
                <p class="text-sm text-tomato-600">{{ $error }}</p>
            @endforeach
            @if (session('status') === 'profile-information-updated')
                <p class="text-sm text-leaf-700">Saved.</p>
            @endif
            <button class="btn-primary">Save details</button>
        </form>

        <form method="POST" action="{{ route('user-password.update') }}" class="card space-y-4 p-6">
            @csrf
            @method('PUT')
            <h2 class="font-sans text-lg font-bold text-leaf-900">Change password</h2>
            <div>
                <label class="label" for="current_password">Current password</label>
                <input class="input" id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div>
                <label class="label" for="password">New password</label>
                <input class="input" id="password" type="password" name="password" required autocomplete="new-password">
                <p class="mt-1 text-xs text-leaf-900/50">At least 10 characters with letters and numbers.</p>
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm new password</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            @foreach ($errors->updatePassword->all() as $error)
                <p class="text-sm text-tomato-600">{{ $error }}</p>
            @endforeach
            @if (session('status') === 'password-updated')
                <p class="text-sm text-leaf-700">Password updated.</p>
            @endif
            <button class="btn-primary">Update password</button>

            <div class="border-t border-leaf-900/10 pt-4 text-sm text-leaf-900/60">
                <p>Your data is handled under the Nigeria Data Protection Act. To request a copy or deletion of your data, email
                    <a class="link" href="mailto:{{ config('josveggies.brand.support_email') }}?subject=Data%20request">{{ config('josveggies.brand.support_email') }}</a>.</p>
            </div>
        </form>
    </div>
</x-layouts.account>
