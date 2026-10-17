@props(['title' => 'My account'])

<x-layouts.store :title="$title" :motion="true">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">My account</p>
                <h1 class="mt-2 text-4xl font-semibold text-leaf-900">Hi, {{ str(auth()->user()->name)->before(' ') }}</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-ghost btn-sm">Sign out</button>
            </form>
        </div>

        <nav class="mt-8 flex gap-1 overflow-x-auto rounded-full bg-white p-1 ring-1 ring-leaf-900/5 [scrollbar-width:none]" aria-label="Account">
            @foreach (['account.dashboard' => 'Orders', 'account.credit' => 'Credit', 'account.profile' => 'Profile & security'] as $route => $label)
                <a href="{{ route($route) }}" class="shrink-0 rounded-full px-5 py-2.5 text-sm font-semibold transition {{ request()->routeIs($route.'*') || (request()->routeIs('account.orders.*') && $route === 'account.dashboard') ? 'bg-leaf-600 text-white' : 'text-leaf-900/70 hover:bg-leaf-50' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mt-8">
            {{ $slot }}
        </div>
    </div>
</x-layouts.store>
