<div wire:poll.30s>
    @if ($message)
        <div class="mb-4 rounded-2xl bg-leaf-800 p-4 text-sm font-semibold text-white" role="status">{{ $message }}</div>
    @endif

    @if ($riders->isEmpty())
        <p class="mb-4 rounded-2xl bg-pepper-300/40 p-4 text-sm">No active riders. <a class="link" href="{{ route('console.riders.index') }}">Add riders</a> first.</p>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section>
            <h2 class="font-sans text-lg font-bold">Waiting at hub</h2>
            <div class="mt-3 space-y-5">
                @forelse ($waiting as $zone => $orders)
                    <div>
                        <p class="text-xs font-bold tracking-widest text-leaf-900/50 uppercase">{{ $zone }} · {{ $orders->count() }}</p>
                        <div class="mt-2 space-y-2">
                            @foreach ($orders as $order)
                                <div wire:key="wait-{{ $order->id }}" class="card flex flex-wrap items-center gap-3 p-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold">{{ $order->number }} · {{ $order->user->name }}</p>
                                        <p class="truncate text-xs text-leaf-900/55">{{ $order->delivery_address }}</p>
                                        <p class="text-xs text-leaf-900/55">{{ $order->contact_phone }}</p>
                                    </div>
                                    <select wire:model="riderFor.{{ $order->id }}" class="input w-40 py-2">
                                        <option value="">Rider…</option>
                                        @foreach ($riders as $rider)
                                            <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="assign({{ $order->id }})" class="btn-primary btn-sm">Hand over</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="card p-8 text-center text-sm text-leaf-900/55">No dispatch orders waiting.</div>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="font-sans text-lg font-bold">Out for delivery</h2>
            <div class="mt-3 space-y-2">
                @forelse ($outForDelivery as $handover)
                    <div wire:key="ho-{{ $handover->id }}" class="card flex flex-wrap items-center gap-3 p-4">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $handover->order->number }} · {{ $handover->order->user->name }}</p>
                            <p class="text-xs text-leaf-900/55">{{ $handover->rider->name }} · since {{ $handover->handed_over_at->format('g:ia') }} · {{ $handover->order->dispatchZone?->name }}</p>
                        </div>
                        <button wire:click="outcome({{ $handover->id }}, 'delivered')" class="btn-primary btn-sm">Delivered</button>
                        <button wire:click="outcome({{ $handover->id }}, 'failed')" class="btn-ghost btn-sm">Failed</button>
                    </div>
                @empty
                    <div class="card p-8 text-center text-sm text-leaf-900/55">Nothing out for delivery.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
