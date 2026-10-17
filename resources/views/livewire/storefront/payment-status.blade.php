<div class="mx-auto max-w-lg px-4 py-20 text-center" @unless ($confirmed || $checks > 40) wire:poll.3s="check" @endunless>
    @if ($confirmed)
        <div class="mx-auto grid size-24 place-items-center rounded-full bg-leaf-600 text-white shadow-2xl shadow-leaf-900/30" data-hero-veg>
            <svg class="size-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5" data-draw/></svg>
        </div>
        <h1 class="mt-8 overflow-hidden text-4xl font-semibold text-leaf-900"><span class="block" data-hero-line>Your slot is confirmed!</span></h1>
        <p class="mt-3 text-leaf-900/65" data-hero-fade>Order <strong>{{ $order->number }}</strong>. We've sent a confirmation by email and SMS.</p>
        <a href="{{ route('account.orders.show', $order) }}" class="btn-primary mt-8" data-hero-fade>Track your order</a>
    @elseif ($order->state === \App\Enums\OrderState::Cancelled)
        <h1 class="text-3xl font-semibold text-leaf-900">Payment refunded</h1>
        <p class="mt-3 text-leaf-900/65">Your payment arrived after your slot was released and the Run is full, so it has been refunded in full.</p>
        <a href="{{ route('home') }}" class="btn-ghost mt-8">Back to the shop</a>
    @else
        <div class="mx-auto size-20 animate-spin rounded-full border-4 border-leaf-200 border-t-leaf-600"></div>
        <h1 class="mt-8 text-3xl font-semibold text-leaf-900">Confirming your payment…</h1>
        <p class="mt-3 text-leaf-900/65">This usually takes a few seconds. Please don't close this page.</p>
        @if ($checks > 40)
            <p class="mt-6 text-sm text-leaf-900/60">Still waiting for the bank. You can safely leave — we'll email you as soon as it's confirmed.</p>
            <a href="{{ route('account.orders.show', $order) }}" class="btn-ghost mt-4">View order</a>
        @endif
    @endif
</div>
