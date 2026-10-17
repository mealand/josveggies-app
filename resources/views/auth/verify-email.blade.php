<x-layouts.auth title="Check your inbox" subtitle="We've sent a verification link to {{ auth()->user()->email }}. Verify your email to check out.">
    @if (session('status') === 'verification-link-sent')
        <p class="mb-5 rounded-2xl bg-leaf-50 px-4 py-3 text-sm text-leaf-800">A new link is on its way.</p>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn-primary w-full py-3.5">Resend verification email</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button class="link text-sm">Sign out</button>
    </form>
</x-layouts.auth>
