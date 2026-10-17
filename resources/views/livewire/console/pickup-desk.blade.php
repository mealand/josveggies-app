<div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-6">
        <form wire:submit="lookup" class="card p-6">
            <label for="code" class="label">Pickup code</label>
            <div class="flex gap-2">
                <input id="code" wire:model="code" autocomplete="off" autocapitalize="characters" maxlength="8"
                       class="input font-mono text-2xl tracking-[0.3em] uppercase" placeholder="ABC123" autofocus>
                <button class="btn-primary px-6">Check</button>
            </div>
        </form>

        <div class="card overflow-hidden p-0" data-qr-scanner wire:ignore>
            <div class="relative aspect-[4/3] bg-leaf-950">
                <video class="size-full object-cover" playsinline muted></video>
                <p data-scan-status class="absolute inset-x-0 bottom-0 bg-leaf-950/70 px-4 py-2 text-center text-sm text-white">Camera off</p>
            </div>
            <div class="flex gap-2 p-4">
                <button type="button" data-scan-start class="btn-primary flex-1">Scan QR code</button>
                <button type="button" data-scan-stop class="btn-ghost">Stop</button>
            </div>
        </div>
    </div>

    <div>
        @if ($message)
            <div class="mb-4 rounded-2xl p-4 text-sm font-semibold {{ $success ? 'bg-leaf-600 text-white' : 'bg-tomato-500/10 text-tomato-600' }}" role="status">{{ $message }}</div>
        @endif

        @if ($order)
            <div class="card overflow-hidden">
                <div class="bg-leaf-900 p-6 text-white">
                    <p class="text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Verify the customer</p>
                    <p class="mt-1 font-display text-3xl font-semibold">{{ $order->user->name }}</p>
                    <p class="text-leaf-100/70">{{ $order->number }} · Run {{ $order->run->code }}</p>
                </div>
                <div class="p-6">
                    <x-order-state-badge :state="$order->state" />
                    <ul class="mt-4 space-y-1.5 text-sm">
                        @foreach ($contents as $item)
                            <li class="{{ $item['unavailable'] ? 'text-leaf-900/40 line-through' : '' }}"><strong>{{ $item['quantity'] }}×</strong> {{ $item['name'] }} <span class="text-leaf-900/45">({{ $item['line'] }})</span></li>
                        @endforeach
                    </ul>
                    <div class="mt-6 flex gap-2">
                        @if ($order->state === \App\Enums\OrderState::ReadyForPickup)
                            <button wire:click="collect" class="btn-primary flex-1 py-4 text-base" wire:loading.attr="disabled">✓ Hand over & mark collected</button>
                        @endif
                        <button wire:click="clear" class="btn-ghost">Cancel</button>
                    </div>
                </div>
            </div>
        @elseif (! $message)
            <div class="card grid place-items-center p-12 text-center text-leaf-900/55">
                <x-veg-art variant="leaf" class="w-32 opacity-60" />
                <p class="mt-4">Enter or scan a pickup code to begin.</p>
            </div>
        @endif
    </div>
</div>
