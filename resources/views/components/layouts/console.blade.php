@props(['title' => 'Console', 'actions' => null])

@php
    use App\Enums\Role;
    $user = auth()->user();
    $nav = array_filter([
        ['console.dashboard', 'Dashboard', 'M3 12l9-8 9 8M5 10v10h14V10', true],
        ['console.runs.index', 'Runs', 'M4 6h16M4 12h16M4 18h10', true],
        ['console.abuja.pickup', 'Pickup desk', 'M4 7h16v10H4zM8 11h.01M12 11h4', $user->hasRole(Role::Admin, Role::AbujaOps)],
        ['console.abuja.dispatch', 'Dispatch', 'M3 16h13V6H3zM16 10h4l1 3v3h-5', $user->hasRole(Role::Admin, Role::AbujaOps)],
        ['console.catalogue.boxes.index', 'Catalogue', 'M4 7l8-4 8 4v10l-8 4-8-4z', $user->hasRole(Role::Admin)],
        ['console.zones.index', 'Hub & zones', 'M12 21s-7-6-7-11a7 7 0 1 1 14 0c0 5-7 11-7 11z', $user->hasRole(Role::Admin)],
        ['console.riders.index', 'Riders', 'M5 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM19 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM7 16h10l-3-7h-3', $user->hasRole(Role::Admin, Role::AbujaOps)],
        ['console.reports.index', 'Reports', 'M4 20V10M10 20V4M16 20v-7M22 20H2', $user->hasRole(Role::Admin)],
        ['console.staff.index', 'Staff', 'M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 21a8 8 0 0 1 16 0', $user->hasRole(Role::Admin)],
        ['console.settings.edit', 'Settings', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12l2-1-2-4-2 1-2-1V5h-4v2l-2 1-2-1-2 4 2 1v0l-2 1 2 4 2-1 2 1v2h4v-2l2-1 2 1 2-4z', $user->hasRole(Role::Admin)],
        ['console.audit', 'Audit log', 'M9 12h6M9 16h6M7 3h10l2 2v16H5V3z', $user->hasRole(Role::Admin)],
        ['console.security', 'Security', 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z', true],
    ], fn ($item) => $item[3]);
    $isActive = fn (string $route) => request()->routeIs(str_replace('.index', '', $route).'*') || request()->routeIs($route);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title.' · Console'" />
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-dvh bg-[#f6f5ef]">
    <x-flash />

    <div class="lg:grid lg:grid-cols-[260px_1fr]">
        {{-- Sidebar --}}
        <aside class="sticky top-0 hidden h-dvh flex-col bg-leaf-950 text-leaf-100 lg:flex no-print">
            <div class="px-6 py-6">
                <a href="{{ route('console.dashboard') }}"><x-logo invert /></a>
                <p class="mt-2 text-[11px] font-bold tracking-[0.2em] text-pepper-300 uppercase">Operations Console</p>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3" aria-label="Console">
                @foreach ($nav as [$route, $label, $icon])
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $isActive($route) ? 'bg-white/10 text-white' : 'text-leaf-100/70 hover:bg-white/5 hover:text-white' }}">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/10 p-4">
                <p class="text-sm font-semibold text-white">{{ $user->name }}</p>
                <p class="text-xs text-leaf-100/60">{{ $user->role->label() }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="text-xs font-semibold text-pepper-300 hover:underline">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 pb-24 lg:pb-0">
            <header class="sticky top-0 z-30 border-b border-leaf-900/5 bg-[#f6f5ef]/85 backdrop-blur-xl no-print">
                <div class="flex min-h-16 flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-8">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('console.dashboard') }}" class="lg:hidden"><x-logo mark /></a>
                        <h1 class="font-display text-2xl font-semibold text-leaf-900">{{ $title }}</h1>
                    </div>
                    @if ($actions)
                        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                    @endif
                </div>
            </header>

            <main class="px-4 py-6 sm:px-8">
                {{ $slot }}
            </main>

            <footer class="px-4 pb-8 text-xs text-leaf-900/40 sm:px-8 no-print">JosVeggies · Powered and developed by {{ config('josveggies.brand.powered_by') }}</footer>
        </div>
    </div>

    {{-- Mobile bottom navigation: the console is used one-handed at the market and hub. --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-leaf-900/10 bg-white/95 backdrop-blur lg:hidden no-print" aria-label="Console mobile">
        <div x-data="toggle" class="relative">
            <div class="grid grid-cols-4">
                @foreach (array_slice($nav, 0, 3) as [$route, $label, $icon])
                    <a href="{{ route($route) }}" class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold {{ $isActive($route) ? 'text-leaf-700' : 'text-leaf-900/50' }}">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                        {{ $label }}
                    </a>
                @endforeach
                <button type="button" x-on:click="toggle" class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-leaf-900/50">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    More
                </button>
            </div>
            <div x-show="open" x-cloak x-transition class="absolute inset-x-2 bottom-full mb-2 rounded-2xl bg-leaf-950 p-2 text-white shadow-2xl">
                @foreach (array_slice($nav, 3) as [$route, $label])
                    <a href="{{ route($route) }}" class="block rounded-xl px-4 py-3 text-sm font-semibold hover:bg-white/10">{{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-xl px-4 py-3 text-left text-sm font-semibold text-pepper-300 hover:bg-white/10">Sign out</button>
                </form>
            </div>
        </div>
    </nav>

    @livewireScripts
</body>
</html>
