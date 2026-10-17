<x-layouts.auth title="Staff sign in" subtitle="Operations Console — authorised staff only." :console="true">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <x-field name="email" label="Work email" type="email" autocomplete="username" required autofocus />
        <x-field name="password" label="Password" type="password" autocomplete="current-password" required />
        <button class="btn-primary w-full py-3.5">Continue</button>
    </form>
    <p class="mt-6 text-xs text-leaf-900/50">Two-factor authentication is required for all staff accounts. Activity in the console is logged.</p>
</x-layouts.auth>
