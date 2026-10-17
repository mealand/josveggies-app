<x-layouts.auth title="Reset your password" subtitle="We'll email you a secure link.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
        <button class="btn-primary w-full py-3.5">Email reset link</button>
    </form>
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="link">Back to sign in</a></p>
</x-layouts.auth>
