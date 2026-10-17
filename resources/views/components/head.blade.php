@props(['title' => null, 'description' => 'Fresh vegetables from Jos, pre-ordered in limited slots and delivered to Abuja.'])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · ' : '' }}JosVeggies</title>
<meta name="description" content="{{ $description }}">
<meta name="theme-color" content="#2e6b30">
<meta property="og:title" content="{{ $title ?? 'JosVeggies' }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:site_name" content="JosVeggies">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<script @cspNonce>
    (function () {
        var root = document.documentElement;
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            root.classList.add('js-motion');
            // Fail open: never leave content hidden if the motion bundle cannot load.
            setTimeout(function () {
                if (!root.classList.contains('motion-ready')) root.classList.remove('js-motion');
            }, 3000);
        }
    })();
</script>
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
