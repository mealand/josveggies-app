<div x-data="cartOpener">
    <button type="button" x-on:click="openCart" data-cart-target
            class="relative grid size-11 place-items-center rounded-full bg-leaf-600 text-white shadow-lg shadow-leaf-900/20 transition hover:bg-leaf-700"
            aria-label="Open cart, {{ $count }} {{ Str::plural('item', $count) }}">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 8h14l-1.4 10.2a2 2 0 0 1-2 1.8H8.4a2 2 0 0 1-2-1.8L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>
        </svg>
        @if ($count > 0)
            <span wire:key="cart-count-{{ $count }}" class="absolute -top-1 -right-1 grid min-w-5 place-items-center rounded-full bg-tomato-500 px-1 text-[11px] font-bold ring-2 ring-cream-50">
                {{ $count }}
            </span>
        @endif
    </button>
</div>
