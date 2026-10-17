@php
    use App\Enums\OrderState;
    use App\Support\Money;

    $steps = OrderState::timeline($order->fulfilment);
    $currentIndex = array_search($order->state, $steps, true);
    // Failed dispatch returns to "Ready" but keep progress readable.
    $progress = $currentIndex === false ? 0 : (int) round($currentIndex / max(1, count($steps) - 1) * 100);
    $isPaid = in_array($order->state, OrderState::paid(), true);
    $run = $order->run;
@endphp

<x-layouts.account :title="'Order '.$order->number">
    <a href="{{ route('account.dashboard') }}" class="text-sm font-semibold text-leaf-700 hover:underline">← All orders</a>

    <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <div class="card p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-leaf-900/55">Order</p>
                        <h2 class="font-display text-3xl font-semibold text-leaf-900">{{ $order->number }}</h2>
                        <p class="mt-1 text-sm text-leaf-900/60">Delivery {{ $run->delivery_starts_at->format('l j M') }} · {{ $order->fulfilment->label() }}</p>
                    </div>
                    <x-order-state-badge :state="$order->state" class="text-sm" />
                </div>

                @if ($order->state === OrderState::PendingPayment)
                    <div class="mt-6 rounded-2xl bg-pepper-300/30 p-5">
                        <p class="font-semibold">Complete your payment</p>
                        <p class="mt-1 text-sm text-leaf-900/70">Your {{ $order->slots }} {{ Str::plural('slot', $order->slots) }} {{ $order->slots === 1 ? 'is' : 'are' }} held until {{ $order->hold_expires_at->format('g:ia') }}.</p>
                        <form method="POST" action="{{ route('account.orders.pay', $order) }}" class="mt-4">
                            @csrf
                            <button class="btn-accent">Pay {{ Money::format($order->total_kobo) }}</button>
                        </form>
                    </div>
                @elseif (in_array($order->state, [OrderState::Expired, OrderState::Cancelled], true))
                    <p class="mt-6 rounded-2xl bg-leaf-900/5 p-5 text-sm text-leaf-900/70">{{ $order->state->customerMessage($order->fulfilment) }}{{ $order->cancellation_reason ? ' — '.$order->cancellation_reason : '' }}.</p>
                @endif

                @if ($isPaid)
                    <ol class="relative mt-8 space-y-6 pl-10">
                        <span class="absolute top-2 bottom-2 left-[15px] w-0.5 rounded bg-leaf-100" aria-hidden="true">
                            <span class="block w-full rounded bg-leaf-600" data-timeline-progress="{{ $progress }}" style="height: {{ $progress }}%"></span>
                        </span>
                        @foreach ($steps as $i => $step)
                            @php $done = $currentIndex !== false && $i <= $currentIndex; @endphp
                            <li class="relative" data-timeline-step>
                                <span class="absolute top-0.5 -left-10 grid size-8 place-items-center rounded-full text-xs font-bold ring-4 ring-white {{ $done ? 'bg-leaf-600 text-white' : 'bg-leaf-100 text-leaf-900/40' }} {{ $i === $currentIndex ? 'scale-110 shadow-lg shadow-leaf-900/25' : '' }}">
                                    {{ $done ? '✓' : $i + 1 }}
                                </span>
                                <p class="font-semibold {{ $done ? 'text-leaf-900' : 'text-leaf-900/40' }}">{{ $step->customerMessage($order->fulfilment) }}</p>
                                @if ($i === $currentIndex && $order->state === OrderState::ReadyForPickup && $run->pickup_ends_at)
                                    <p class="text-sm text-tomato-600">Collect by {{ $run->pickup_ends_at->format('l j M, g:ia') }}</p>
                                @endif
                            </li>
                        @endforeach
                        @if ($order->state === OrderState::Forfeited)
                            <li class="relative font-semibold text-tomato-600">Not collected in time — forfeited per policy.</li>
                        @endif
                    </ol>
                @endif
            </div>

            {{-- Contents & substitutions (BR-14) --}}
            <div class="card p-6 sm:p-8">
                <h3 class="font-sans text-lg font-bold text-leaf-900">What's in your order</h3>
                <ul class="mt-4 divide-y divide-leaf-900/5 text-sm">
                    @foreach ($contents as $row)
                        <li class="flex items-center justify-between gap-4 py-2.5">
                            <span>
                                <span class="font-semibold">{{ $row['quantity'] }} ×</span> {{ $row['name'] }}
                                <span class="text-leaf-900/45">· {{ $row['line'] }}</span>
                                @if ($row['note'])
                                    <span class="chip ml-1 {{ $row['unavailable'] ? 'bg-tomato-500/10 text-tomato-600' : 'bg-pepper-300/40 text-leaf-950' }}">{{ $row['note'] }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
                @if ($order->substitutions->isNotEmpty())
                    <p class="mt-4 rounded-2xl bg-leaf-50 p-4 text-sm text-leaf-900/75">Substitutes are always of equal or greater value. Where no fair substitute existed, the value has been added to your credit.</p>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            @if ($isPaid && $order->pickup_code && ! in_array($order->state, [OrderState::Collected, OrderState::Delivered, OrderState::Forfeited], true))
                <div class="card overflow-hidden text-center">
                    <div class="bg-leaf-900 px-6 py-4 text-white">
                        <p class="text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Pickup code</p>
                        <div x-data="copyText" data-copy="{{ $order->pickup_code }}">
                            <button type="button" x-on:click="copy" class="mt-1 font-mono text-4xl font-bold tracking-[0.3em]">{{ $order->pickup_code }}</button>
                            <p class="text-xs text-leaf-200" x-show="copied" x-cloak>Copied!</p>
                        </div>
                    </div>
                    <img src="{{ route('account.orders.qr', $order) }}" alt="QR code for pickup code {{ $order->pickup_code }}" class="mx-auto size-56 p-4" width="224" height="224">
                    <p class="px-6 pb-6 text-xs text-leaf-900/55">Show this at the hub{{ $order->fulfilment->value === 'dispatch' ? ' or to your rider' : '' }}. Keep it private.</p>
                </div>
            @endif

            <div class="card p-6 text-sm">
                <h3 class="font-sans font-bold text-leaf-900">Payment</h3>
                <dl class="mt-3 space-y-2">
                    <div class="flex justify-between"><dt class="text-leaf-900/60">Items</dt><dd>{{ Money::format($order->subtotal_kobo) }}</dd></div>
                    @if ($order->dispatch_fee_kobo)
                        <div class="flex justify-between"><dt class="text-leaf-900/60">Dispatch ({{ $order->dispatchZone?->name }})</dt><dd>{{ Money::format($order->dispatch_fee_kobo) }}</dd></div>
                    @endif
                    @if ($order->credit_applied_kobo)
                        <div class="flex justify-between"><dt class="text-leaf-900/60">Credit used</dt><dd>−{{ Money::format($order->credit_applied_kobo) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-leaf-900/10 pt-2 font-semibold"><dt>Paid</dt><dd>{{ Money::format($order->total_kobo) }}</dd></div>
                </dl>
            </div>

            @if ($order->fulfilment->value === 'pickup')
                <div class="card p-6 text-sm">
                    <h3 class="font-sans font-bold text-leaf-900">Pickup hub</h3>
                    <p class="mt-2 font-semibold">{{ $run->hub->name }}</p>
                    <p class="text-leaf-900/65">{{ $run->hub->address }}</p>
                    @if ($run->hub->directions)<p class="mt-1 text-leaf-900/50">{{ $run->hub->directions }}</p>@endif
                </div>
            @endif
        </aside>
    </div>
</x-layouts.account>
