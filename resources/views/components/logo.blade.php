@props(['mark' => false, 'invert' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 40 40" class="size-9 shrink-0" aria-hidden="true">
        <circle cx="20" cy="22" r="15" class="{{ $invert ? 'fill-white' : 'fill-tomato-500' }}"/>
        <path d="M20 7c-1.5 3-1.2 5.6.6 8.2" class="{{ $invert ? 'stroke-pepper-300' : 'stroke-leaf-700' }}" stroke-width="2.4" stroke-linecap="round" fill="none"/>
        <path d="M20.5 9.5c3.8-4.6 9.4-4.4 11.8-3.3-1.2 4.7-6 7.4-11.8 3.3Z" class="{{ $invert ? 'fill-pepper-300' : 'fill-leaf-600' }}"/>
        <path d="M19.6 10c-3.2-3.6-7.6-3.5-9.6-2.6 1 3.7 4.9 5.8 9.6 2.6Z" class="{{ $invert ? 'fill-pepper-400' : 'fill-leaf-500' }}"/>
        <ellipse cx="14.5" cy="20" rx="3" ry="4.5" class="fill-white/35" transform="rotate(-25 14.5 20)"/>
    </svg>
    @unless ($mark)
        <span class="font-display text-[1.35rem] leading-none font-bold tracking-tight {{ $invert ? 'text-white' : 'text-leaf-800' }}">
            Jos<span class="{{ $invert ? 'text-pepper-300' : 'text-tomato-500' }}">Veggies</span>
        </span>
    @endunless
</span>
