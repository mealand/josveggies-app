<x-layouts.auth title="Two-factor check" subtitle="Enter the 6-digit code from your authenticator app." :console="true">
    <div x-data="toggle">
        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5" x-show="closed">
            @csrf
            <x-field name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" autofocus />
            <button class="btn-primary w-full py-3.5">Verify</button>
        </form>
        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5" x-show="open" x-cloak>
            @csrf
            <x-field name="recovery_code" label="Recovery code" autocomplete="one-time-code" />
            <button class="btn-primary w-full py-3.5">Use recovery code</button>
        </form>
        <button type="button" x-on:click="toggle" class="link mt-6 text-sm">Use a recovery code instead</button>
    </div>
</x-layouts.auth>
