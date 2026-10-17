@props(['title' => null, 'description' => null, 'motion' => false])

<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title" :description="$description ?? 'Fresh vegetables from Jos, pre-ordered in limited slots and delivered to Abuja.'" />
</head>
<body class="min-h-dvh" @if ($motion) data-motion @endif>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-full focus:bg-white focus:px-4 focus:py-2">Skip to content</a>

    <header class="sticky top-0 z-40 border-b border-leaf-900/5 bg-cream-50/80 backdrop-blur-xl no-print" x-data="toggle">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="JosVeggies home">
                <x-logo />
            </a>

            <nav class="hidden items-center gap-1 text-sm font-semibold text-leaf-900/80 md:flex" aria-label="Main">
                <a href="{{ route('home') }}#boxes" class="rounded-full px-4 py-2 transition hover:bg-leaf-100 hover:text-leaf-900">Boxes</a>
                <a href="{{ route('pages.how') }}" class="rounded-full px-4 py-2 transition hover:bg-leaf-100 hover:text-leaf-900">How it works</a>
                <a href="{{ route('pages.faq') }}" class="rounded-full px-4 py-2 transition hover:bg-leaf-100 hover:text-leaf-900">FAQ</a>
                <a href="{{ route('pages.contact') }}" class="rounded-full px-4 py-2 transition hover:bg-leaf-100 hover:text-leaf-900">Contact</a>
            </nav>

            <div class="flex items-center gap-2">
                <livewire:storefront.cart-button />

                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('console.dashboard') : route('account.dashboard') }}" class="btn-ghost btn-sm hidden sm:inline-flex">
                        {{ auth()->user()->isStaff() ? 'Console' : 'My account' }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex">Sign in</a>
                @endauth

                <button type="button" class="grid size-11 place-items-center rounded-full md:hidden" x-on:click="toggle" aria-label="Menu">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak x-transition.origin.top class="border-t border-leaf-900/5 bg-cream-50 px-4 pb-6 pt-2 md:hidden">
            <nav class="flex flex-col text-lg font-semibold" aria-label="Mobile">
                <a href="{{ route('home') }}#boxes" x-on:click="close" class="border-b border-leaf-900/5 py-3">Boxes</a>
                <a href="{{ route('pages.how') }}" class="border-b border-leaf-900/5 py-3">How it works</a>
                <a href="{{ route('pages.faq') }}" class="border-b border-leaf-900/5 py-3">FAQ</a>
                <a href="{{ route('pages.contact') }}" class="border-b border-leaf-900/5 py-3">Contact</a>
                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('console.dashboard') : route('account.dashboard') }}" class="py-3 text-leaf-700">{{ auth()->user()->isStaff() ? 'Operations Console' : 'My account' }}</a>
                @else
                    <a href="{{ route('login') }}" class="py-3 text-leaf-700">Sign in</a>
                    <a href="{{ route('register') }}" class="py-3 text-leaf-700">Create account</a>
                @endauth
            </nav>
        </div>
    </header>

    <x-flash />

    <main id="main">
        {{ $slot }}
    </main>

    <livewire:storefront.cart-drawer />

    <footer class="relative mt-24 overflow-hidden bg-leaf-900 text-leaf-100 no-print">
        <div class="grain absolute inset-0"></div>
        <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-4 lg:px-8">
            <div class="md:col-span-2">
                <x-logo invert />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-leaf-100/70">
                    {{ config('josveggies.brand.tagline') }} Pre-order in limited slots — we buy only what's been ordered, fresh on market day.
                </p>
                <a href="https://wa.me/{{ config('josveggies.brand.support_whatsapp') }}" rel="noopener" target="_blank" class="mt-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                    <span class="size-2 rounded-full bg-leaf-300"></span> Chat with us on WhatsApp
                </a>
            </div>
            <div>
                <h2 class="font-sans text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Shop</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a class="hover:text-white" href="{{ route('home') }}#boxes">Boxes</a></li>
                    <li><a class="hover:text-white" href="{{ route('pages.how') }}">How it works</a></li>
                    <li><a class="hover:text-white" href="{{ route('pages.faq') }}">FAQ</a></li>
                    <li><a class="hover:text-white" href="{{ route('pages.contact') }}">Contact</a></li>
                </ul>
            </div>
            <div>
                <h2 class="font-sans text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Policies</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach (\App\Models\Policy::SLUGS as $slug => $label)
                        <li><a class="hover:text-white" href="{{ route('pages.policy', $slug) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="relative border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs text-leaf-100/60 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>© {{ now()->year }} JosVeggies. All prices in naira. Times in West Africa Time.</p>
                <p>Powered and developed by <span class="font-semibold text-leaf-100/90">{{ config('josveggies.brand.powered_by') }}</span></p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
