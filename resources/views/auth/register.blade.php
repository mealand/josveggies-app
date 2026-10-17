<x-layouts.auth title="Create your account" subtitle="Reserve slots, track your boxes and keep your credit in one place.">
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <x-field name="name" label="Full name" autocomplete="name" required autofocus />
        <x-field name="email" label="Email" type="email" autocomplete="email" required />
        <x-field name="phone" label="Phone number" type="tel" inputmode="tel" autocomplete="tel" placeholder="0803 123 4567" hint="We text your pickup code and reminders here." required />
        <x-field name="password" label="Password" type="password" autocomplete="new-password" hint="At least 10 characters with letters and numbers." required />
        <x-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
        <label class="flex gap-3 text-sm">
            <input type="checkbox" name="privacy" value="1" class="mt-0.5 size-5 shrink-0 rounded border-leaf-900/30 text-leaf-600" required>
            <span>I agree to the <a href="{{ route('pages.policy', 'privacy') }}" target="_blank" class="link">privacy policy</a> and <a href="{{ route('pages.policy', 'terms') }}" target="_blank" class="link">terms</a>.</span>
        </label>
        @error('privacy') <p class="text-sm text-tomato-600">{{ $message }}</p> @enderror
        <button class="btn-primary w-full py-3.5">Create account</button>
    </form>
    <p class="mt-8 text-center text-sm text-leaf-900/60">Already have an account? <a href="{{ route('login') }}" class="link">Sign in</a></p>
</x-layouts.auth>
