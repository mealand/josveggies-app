<x-layouts.auth title="Welcome back" subtitle="Sign in to order and track your boxes.">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
        <x-field name="password" label="Password" type="password" autocomplete="current-password" required />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-leaf-900/30 text-leaf-600"> Keep me signed in</label>
            <a href="{{ route('password.request') }}" class="link">Forgot password?</a>
        </div>
        <button class="btn-primary w-full py-3.5">Sign in</button>
    </form>
    <p class="mt-8 text-center text-sm text-leaf-900/60">New to JosVeggies? <a href="{{ route('register') }}" class="link">Create an account</a></p>
</x-layouts.auth>
