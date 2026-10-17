@props(['title', 'subtitle' => null, 'console' => false])

<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title" />
</head>
<body class="min-h-dvh" data-motion>
    <x-flash />
    <div class="grid min-h-dvh lg:grid-cols-2">
        <div class="relative hidden overflow-hidden {{ $console ? 'bg-leaf-950' : 'bg-leaf-800' }} lg:block">
            <div class="grain absolute inset-0"></div>
            <div class="absolute -top-20 -left-20 size-96 rounded-full bg-leaf-500/30 blur-3xl" data-parallax="0.2"></div>
            <div class="absolute right-10 bottom-10 size-72 rounded-full bg-tomato-500/25 blur-3xl"></div>
            <div class="relative flex h-full flex-col justify-between p-12 text-white">
                <a href="{{ route('home') }}"><x-logo invert /></a>
                <div>
                    @if ($console)
                        <p class="text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Operations Console</p>
                        <p class="mt-4 max-w-md font-display text-4xl leading-tight font-semibold" data-hero-line>Where pre-orders become a smooth market day.</p>
                    @else
                        <div class="w-56" data-hero-veg><x-veg-art /></div>
                        <p class="mt-8 max-w-md font-display text-4xl leading-tight font-semibold" data-hero-line>Fresh from Jos. Picked up in Abuja.</p>
                    @endif
                </div>
                <p class="text-xs text-leaf-100/60">Powered and developed by {{ config('josveggies.brand.powered_by') }}</p>
            </div>
        </div>

        <main class="flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="lg:hidden"><x-logo /></a>
                <h1 class="mt-8 text-4xl font-semibold text-leaf-900 lg:mt-0" data-hero-fade>{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 text-leaf-900/60" data-hero-fade>{{ $subtitle }}</p>
                @endif
                <div class="mt-8" data-hero-fade>
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>
