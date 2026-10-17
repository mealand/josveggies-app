<div x-data="cartDrawer" class="no-print">
    <div x-show="open" x-cloak x-transition.opacity.duration.300ms x-on:click="hide"
         class="fixed inset-0 z-50 bg-leaf-950/40 backdrop-blur-sm"></div>

    <aside x-show="open" x-cloak
           x-transition:enter="transition duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-300 ease-in"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-cream-50 shadow-2xl"
           role="dialog" aria-modal="true" aria-label="Your order">
        <div class="flex items-center justify-between border-b border-leaf-900/10 px-6 py-5">
            <div>
                <p class="eyebrow">Your order</p>
                <h2 class="mt-1 text-2xl font-semibold text-leaf-900">Cart</h2>
            </div>
            <button type="button" x-on:click="hide" class="grid size-11 place-items-center rounded-full hover:bg-leaf-100" aria-label="Close cart">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-4">
            @forelse ($summary['lines'] as $line)
                <div wire:key="line-{{ $line['type'] }}-{{ $line['id'] }}" class="flex items-center gap-4 border-b border-leaf-900/5 py-4">
                    <div class="grid size-14 shrink-0 place-items-center rounded-2xl {{ $line['type'] === 'box' ? 'bg-leaf-100' : 'bg-tomato-500/10' }}">
                        <x-veg-art :variant="$line['type'] === 'box' ? 'tomato' : 'pepper'" class="w-11" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-leaf-900">{{ $line['name'] }}</p>
                        <p class="text-xs text-leaf-900/55">
                            {{ \App\Support\Money::format($line['unit_price_kobo']) }} each
                            @if ($line['type'] === 'box') · uses {{ $line['quantity'] }} {{ Str::plural('slot', $line['quantity']) }} @endif
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <button type="button" wire:click="decrement('{{ $line['type'] }}', {{ $line['id'] }})" class="grid size-8 place-items-center rounded-full bg-white ring-1 ring-leaf-900/10 hover:ring-leaf-600" aria-label="Decrease">−</button>
                            <span class="w-6 text-center text-sm font-bold">{{ $line['quantity'] }}</span>
                            <button type="button" wire:click="increment('{{ $line['type'] }}', {{ $line['id'] }})" class="grid size-8 place-items-center rounded-full bg-white ring-1 ring-leaf-900/10 hover:ring-leaf-600" aria-label="Increase">+</button>
                            <button type="button" wire:click="remove('{{ $line['type'] }}', {{ $line['id'] }})" class="ml-2 text-xs font-semibold text-tomato-600 hover:underline">Remove</button>
                        </div>
                    </div>
                    <p class="font-display text-lg font-semibold">{{ \App\Support\Money::format($line['line_total_kobo']) }}</p>
                </div>
            @empty
                <div class="grid h-full place-items-center py-16 text-center">
                    <div>
                        <x-veg-art variant="leaf" class="mx-auto w-40 animate-float opacity-80" />
                        <p class="mt-6 font-display text-xl text-leaf-900">Your cart is empty</p>
                        <p class="mt-1 text-sm text-leaf-900/60">Pick a box to reserve your slot in the next Run.</p>
                        <a href="{{ route('home') }}#boxes" x-on:click="hide" class="btn-primary mt-6">Browse boxes</a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($summary['lines'] !== [])
            <div class="border-t border-leaf-900/10 bg-white px-6 py-5">
                @if ($run && $run->slotsRemaining() < $summary['box_count'])
                    <p class="mb-3 rounded-xl bg-tomato-500/10 px-3 py-2 text-sm text-tomato-600">
                        Only {{ $run->slotsRemaining() }} {{ Str::plural('slot', $run->slotsRemaining()) }} left — reduce your boxes to continue.
                    </p>
                @endif
                @if ($summary['box_count'] === 0)
                    <p class="mb-3 rounded-xl bg-pepper-300/30 px-3 py-2 text-sm text-leaf-900">Add-ons are sold alongside a box — add at least one box.</p>
                @endif
                <div class="flex items-center justify-between text-sm">
                    <span class="text-leaf-900/60">Subtotal</span>
                    <span class="font-display text-2xl font-semibold">{{ \App\Support\Money::format($summary['subtotal_kobo']) }}</span>
                </div>
                <p class="mt-1 text-xs text-leaf-900/50">Pickup is free. Rider dispatch fees are added at checkout.</p>
                <a href="{{ route('checkout') }}" class="btn-accent mt-4 w-full {{ $summary['box_count'] === 0 ? 'pointer-events-none opacity-50' : '' }}">
                    Checkout →
                </a>
            </div>
        @endif
    </aside>
</div>
