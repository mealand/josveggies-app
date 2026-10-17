@props(['eyebrow' => null, 'title', 'lead' => null])

<section class="relative overflow-hidden border-b border-leaf-900/5 bg-cream-100">
    <div class="absolute -top-24 -right-24 size-80 rounded-full bg-leaf-200/50 blur-3xl" data-parallax="0.2"></div>
    <div class="relative mx-auto max-w-4xl px-4 py-16 sm:px-6 sm:py-20">
        @if ($eyebrow)
            <p class="eyebrow" data-hero-fade>{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 overflow-hidden text-4xl font-semibold text-leaf-900 sm:text-6xl"><span class="block" data-hero-line>{{ $title }}</span></h1>
        @if ($lead)
            <p class="mt-5 max-w-2xl text-lg text-leaf-900/65" data-hero-fade>{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
