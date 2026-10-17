<x-layouts.auth title="Confirm your password" subtitle="This is a sensitive action. Please confirm your password to continue.">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <x-field name="password" label="Password" type="password" autocomplete="current-password" required autofocus />
        <button class="btn-primary w-full py-3.5">Confirm</button>
    </form>
</x-layouts.auth>
