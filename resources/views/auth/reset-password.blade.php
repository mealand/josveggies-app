<x-layouts.auth title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-field name="email" label="Email" type="email" :value="$request->email" autocomplete="email" required />
        <x-field name="password" label="New password" type="password" autocomplete="new-password" hint="At least 10 characters with letters and numbers." required />
        <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
        <button class="btn-primary w-full py-3.5">Reset password</button>
    </form>
</x-layouts.auth>
