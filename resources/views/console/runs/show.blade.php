@php
    use App\Enums\Role;
    use App\Enums\RunState;
    $user = auth()->user();
    $flow = [RunState::Draft, RunState::Scheduled, RunState::Open, RunState::Closed, RunState::Procuring, RunState::Packed, RunState::InTransit, RunState::Arrived, RunState::PickupClosed, RunState::Reconciled];
    $currentIndex = array_search($run->state, $flow, true);
    $actionLabels = [
        'scheduled' => 'Publish (schedule)', 'draft' => 'Back to draft', 'open' => 'Open orders now', 'closed' => 'Close orders now',
        'procuring' => 'Start market day', 'packed' => 'Mark all packed', 'in_transit' => 'Departed Jos',
        'arrived' => 'Mark arrived at hub', 'pickup_closed' => 'Close pickup window', 'reconciled' => 'Reconcile Run', 'cancelled' => 'Cancel Run',
    ];
@endphp

<x-layouts.console :title="'Run '.$run->code">
    <x-slot:actions>
        @can('admin')
            @if (in_array($run->state, [RunState::Draft, RunState::Scheduled, RunState::Open], true))
                <a href="{{ route('console.runs.edit', $run) }}" class="btn-ghost btn-sm">Edit</a>
            @endif
            <a href="{{ route('console.reports.run', $run) }}" class="btn-ghost btn-sm">P&amp;L</a>
        @endcan
    </x-slot:actions>

    {{-- State stepper --}}
    <div class="card overflow-x-auto p-5">
        <ol class="flex min-w-[760px] items-center gap-2">
            @foreach ($flow as $i => $state)
                <li class="flex flex-1 items-center gap-2">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full text-xs font-bold {{ $currentIndex !== false && $i < $currentIndex ? 'bg-leaf-600 text-white' : ($i === $currentIndex ? 'bg-tomato-500 text-white ring-4 ring-tomato-500/20' : 'bg-leaf-100 text-leaf-900/40') }}">{{ $currentIndex !== false && $i < $currentIndex ? '✓' : $i + 1 }}</span>
                    <span class="text-xs font-semibold {{ $i === $currentIndex ? 'text-leaf-900' : 'text-leaf-900/45' }}">{{ $state->label() }}</span>
                    @if (! $loop->last)<span class="h-px flex-1 bg-leaf-900/10"></span>@endif
                </li>
            @endforeach
        </ol>
        @if ($run->state === RunState::Cancelled)
            <p class="mt-3 text-sm font-semibold text-tomato-600">This Run was cancelled {{ $run->cancelled_at?->format('j M H:i') }}. Paid orders were refunded.</p>
        @endif
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat label="Confirmed slots" :value="$run->slots_confirmed.' / '.$run->slot_capacity" :hint="$run->fillPercent().'% full'" tone="dark" />
                <x-stat label="Held at checkout" :value="$run->slots_held" hint="Released after the hold expires" />
                <x-stat label="Paid orders" :value="$orders->filter(fn ($o) => in_array($o->state, \App\Enums\OrderState::paid(), true))->count()" />
                <x-stat label="Delivery" :value="$run->delivery_starts_at->format('D j M')" :hint="'from '.$run->delivery_starts_at->format('g:ia')" />
            </div>

            <div class="card overflow-x-auto">
                <div class="flex items-center justify-between px-5 py-4">
                    <h2 class="font-sans text-lg font-bold">Orders</h2>
                    <p class="text-xs text-leaf-900/50">
                        @foreach ($stateCounts as $state => $count) {{ \App\Enums\OrderState::from($state)->label() }}: {{ $count }}@if (! $loop->last) · @endif @endforeach
                    </p>
                </div>
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                        <tr><th class="px-5 py-2.5">Order</th><th class="px-5 py-2.5">Customer</th><th class="px-5 py-2.5">Boxes</th><th class="px-5 py-2.5">Fulfilment</th><th class="px-5 py-2.5">State</th></tr>
                    </thead>
                    <tbody class="divide-y divide-leaf-900/5">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-5 py-2.5 font-semibold">{{ $order->number }}</td>
                                <td class="px-5 py-2.5">{{ $order->user->name }}</td>
                                <td class="px-5 py-2.5 tabular-nums">{{ $order->slots }}</td>
                                <td class="px-5 py-2.5">{{ $order->fulfilment->label() }}{{ $order->dispatchZone ? ' · '.$order->dispatchZone->name : '' }}</td>
                                <td class="px-5 py-2.5"><x-order-state-badge :state="$order->state" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-leaf-900/55">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="font-sans text-lg font-bold">Actions</h2>
                <div class="mt-4 space-y-2">
                    @if ($run->state === RunState::Procuring && ! $run->purchased_at && $user->hasRole(Role::Admin, Role::JosOps))
                        <form method="POST" action="{{ route('console.runs.purchased', $run) }}">
                            @csrf
                            <button class="btn-accent w-full py-4 text-base">✓ Everything purchased</button>
                        </form>
                    @endif
                    @forelse ($actions as $to)
                        @continue($to === RunState::Packed && ! $run->purchased_at)
                        <form method="POST" action="{{ route('console.runs.transition', $run) }}"
                              @if ($to === RunState::Cancelled) x-data="confirmSubmit" x-on:submit="check" data-message="Cancel this Run? All paid orders will be refunded." @endif>
                            @csrf
                            <input type="hidden" name="to" value="{{ $to->value }}">
                            <button class="{{ $to === RunState::Cancelled ? 'btn-danger' : 'btn-primary' }} w-full py-3.5 text-base">{{ $actionLabels[$to->value] ?? $to->label() }}</button>
                        </form>
                    @empty
                        <p class="text-sm text-leaf-900/55">No actions available for your role right now.</p>
                    @endforelse
                </div>
                @if (in_array($run->state, [RunState::Scheduled, RunState::Open], true))
                    <p class="mt-3 text-xs text-leaf-900/50">Opening and closing happen automatically at the scheduled times.</p>
                @endif
            </div>

            <div class="card space-y-3 p-6 text-sm">
                <h2 class="font-sans text-lg font-bold">Details</h2>
                <p><span class="text-leaf-900/55">Hub:</span> {{ $run->hub->name }}</p>
                <p><span class="text-leaf-900/55">Orders:</span> {{ $run->opens_at->format('D j M H:i') }} → {{ $run->closes_at->format('D j M H:i') }}</p>
                @can('view-market-day')
                    <p class="rounded-xl bg-tomato-500/10 px-3 py-2"><span class="text-tomato-600">Market day (confidential):</span> <strong>{{ $run->market_day->format('l j M Y') }}</strong></p>
                @endcan
                @if ($run->arrived_at)
                    <p><span class="text-leaf-900/55">Arrived:</span> {{ $run->arrived_at->format('D j M H:i') }}</p>
                    <p><span class="text-leaf-900/55">Pickup ends:</span> <strong>{{ $run->pickup_ends_at->format('D j M H:i') }}</strong></p>
                @endif
                <p><span class="text-leaf-900/55">Boxes:</span> {{ $run->boxTemplates->pluck('name')->join(', ') }}</p>
                @can('view-market-day')
                    @if ($run->internal_notes)
                        <p class="whitespace-pre-line rounded-xl bg-cream-100 p-3">{{ $run->internal_notes }}</p>
                    @endif
                @endcan
            </div>

            @can('jos-ops')
                <div class="card space-y-2 p-6">
                    <h2 class="font-sans text-lg font-bold">Jos market day</h2>
                    <a class="btn-ghost w-full" href="{{ route('console.jos.procurement', $run) }}">Procurement list</a>
                    <a class="btn-ghost w-full" href="{{ route('console.jos.packing', $run) }}">Packing lists</a>
                    <a class="btn-ghost w-full" href="{{ route('console.jos.substitutions', $run) }}">Substitutions</a>
                    <a class="btn-ghost w-full" href="{{ route('console.jos.labels.pdf', $run) }}">Print labels (PDF)</a>
                </div>
            @endcan
            @can('abuja-ops')
                <div class="card space-y-2 p-6">
                    <h2 class="font-sans text-lg font-bold">Abuja hub</h2>
                    <a class="btn-ghost w-full" href="{{ route('console.abuja.pickup') }}">Pickup desk</a>
                    <a class="btn-ghost w-full" href="{{ route('console.abuja.dispatch') }}">Dispatch board</a>
                    <a class="btn-ghost w-full" href="{{ route('console.abuja.forfeitures', $run) }}">Forfeitures</a>
                </div>
            @endcan
        </aside>
    </div>
</x-layouts.console>
