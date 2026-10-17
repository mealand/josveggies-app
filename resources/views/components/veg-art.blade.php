{{-- Stylised produce illustrations used until real product photos are uploaded. --}}
@props(['variant' => 'tomato'])

<svg viewBox="0 0 240 180" {{ $attributes->merge(['class' => 'w-full h-auto']) }} aria-hidden="true">
    @switch($variant)
        @case('leaf')
            {{-- Family box: cabbage, carrots, onions, pepper --}}
            <ellipse cx="120" cy="160" rx="96" ry="12" class="fill-leaf-900/10"/>
            <circle cx="92" cy="104" r="46" class="fill-leaf-400"/>
            <path d="M58 92c18-30 50-34 70-16-24-4-44 6-58 28Z" class="fill-leaf-300"/>
            <path d="M92 60c-4 22 2 50 0 90" class="stroke-leaf-600" stroke-width="3" fill="none"/>
            <path d="M150 70l26 78-14 4-22-78Z" class="fill-tomato-400"/>
            <path d="M152 70c-6-12-2-22 6-26 0 10 4 18-2 26Zm4-2c4-14 14-18 22-16-4 8-12 16-22 16Z" class="fill-leaf-500"/>
            <circle cx="182" cy="128" r="22" class="fill-pepper-300"/>
            <path d="M182 106c2-8 8-10 12-8" class="stroke-leaf-700" stroke-width="3" stroke-linecap="round" fill="none"/>
            <circle cx="140" cy="140" r="16" class="fill-cream-200"/>
            <path d="M140 124c-2-6 0-10 4-12" class="stroke-leaf-600" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            @break

        @case('salad')
            {{-- Salad box: lettuce, cucumber, carrot, green pepper --}}
            <ellipse cx="120" cy="160" rx="96" ry="12" class="fill-leaf-900/10"/>
            <path d="M40 118c0-40 30-62 58-62s56 22 56 62c0 18-14 30-56 30s-58-12-58-30Z" class="fill-leaf-300"/>
            <path d="M58 116c6-24 22-38 40-38s32 14 38 38" class="stroke-leaf-500" stroke-width="4" fill="none"/>
            <path d="M80 98c6-10 12-14 18-14" class="stroke-leaf-200" stroke-width="3" stroke-linecap="round" fill="none"/>
            <rect x="140" y="60" width="26" height="92" rx="13" transform="rotate(18 153 106)" class="fill-leaf-600"/>
            <circle cx="150" cy="80" r="3" class="fill-leaf-300"/><circle cx="146" cy="104" r="3" class="fill-leaf-300"/>
            <path d="M186 74l14 72-12 3-12-72Z" class="fill-tomato-400"/>
            <circle cx="196" cy="140" r="18" class="fill-leaf-500"/>
            @break

        @case('pepper')
            {{-- Add-on: peppers --}}
            <ellipse cx="120" cy="160" rx="80" ry="10" class="fill-leaf-900/10"/>
            <path d="M84 70c-24 0-34 30-24 58 8 22 30 26 40 10 10 16 32 12 40-10 10-28 0-58-24-58-6 0-12 4-16 8-4-4-10-8-16-8Z" class="fill-tomato-500"/>
            <path d="M100 74c0-14 6-22 16-24" class="stroke-leaf-600" stroke-width="5" stroke-linecap="round" fill="none"/>
            <path d="M74 90c-4 10-4 24 2 34" class="stroke-white/40" stroke-width="5" stroke-linecap="round" fill="none"/>
            @break

        @default
            {{-- Soup pot box: tomatoes, peppers, onions, ugu, scent leaf --}}
            <ellipse cx="120" cy="160" rx="96" ry="12" class="fill-leaf-900/10"/>
            <circle cx="86" cy="116" r="36" class="fill-tomato-500"/>
            <circle cx="140" cy="126" r="30" class="fill-tomato-400"/>
            <path d="M86 80c-6-8-16-8-22-4 8 2 14 6 22 4Zm0 0c4-8 14-10 20-6-8 2-14 6-20 6Z" class="fill-leaf-500"/>
            <path d="M74 100c-6 8-8 18-4 28" class="stroke-white/35" stroke-width="5" stroke-linecap="round" fill="none"/>
            <path d="M168 66c-18 0-24 22-18 40 6 16 20 18 26 8 6 10 20 8 24-8 6-18-2-40-20-40-4 0-8 2-12 6Z" class="fill-pepper-400"/>
            <path d="M176 70c0-10 4-16 12-18" class="stroke-leaf-700" stroke-width="4" stroke-linecap="round" fill="none"/>
            <path d="M40 70c20-16 46-12 54 6-20-8-38-6-54-6Z" class="fill-leaf-600"/>
            <path d="M52 52c18-6 34 2 38 14-14-6-26-8-38-14Z" class="fill-leaf-400"/>
    @endswitch
</svg>
