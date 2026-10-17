<x-layouts.console title="Security">
    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <div class="card p-6">
            <h2 class="font-sans text-lg font-bold">Two-factor authentication</h2>
            <p class="mt-1 text-sm text-leaf-900/60">Required for every staff account. Use Google Authenticator, Microsoft Authenticator, Authy or 1Password.</p>

            @if ($confirmed)
                <p class="mt-5 inline-flex items-center gap-2 rounded-full bg-leaf-100 px-4 py-2 text-sm font-semibold text-leaf-800">✓ Enabled since {{ $user->two_factor_confirmed_at->format('j M Y') }}</p>

                <details class="mt-6">
                    <summary class="cursor-pointer text-sm font-semibold text-leaf-700">Show recovery codes</summary>
                    <ul class="mt-3 grid grid-cols-2 gap-2 rounded-2xl bg-cream-100 p-4 font-mono text-sm">
                        @foreach ($user->recoveryCodes() as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="mt-3">
                        @csrf
                        <button class="btn-ghost btn-sm">Regenerate codes</button>
                    </form>
                </details>
            @elseif ($pending)
                <ol class="mt-5 space-y-4 text-sm">
                    <li>1. Scan this QR code with your authenticator app:</li>
                </ol>
                <div class="mt-3 inline-block rounded-2xl bg-white p-3 ring-1 ring-leaf-900/10">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <p class="mt-2 text-xs text-leaf-900/50">Or enter this key: <span class="font-mono">{{ decrypt($user->two_factor_secret) }}</span></p>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-5 space-y-3">
                    @csrf
                    <x-field name="code" label="2. Enter the 6-digit code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" bag="confirmTwoFactorAuthentication" required />
                    <button class="btn-primary">Confirm & enable</button>
                </form>
            @else
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-5">
                    @csrf
                    <button class="btn-primary">Set up two-factor authentication</button>
                </form>
            @endif
        </div>

        <form method="POST" action="{{ route('user-password.update') }}" class="card space-y-4 p-6">
            @csrf
            @method('PUT')
            <h2 class="font-sans text-lg font-bold">Change password</h2>
            <x-field name="current_password" label="Current password" type="password" autocomplete="current-password" bag="updatePassword" required />
            <x-field name="password" label="New password" type="password" autocomplete="new-password" bag="updatePassword" required />
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" bag="updatePassword" required />
            <button class="btn-primary">Update password</button>
        </form>
    </div>
</x-layouts.console>
