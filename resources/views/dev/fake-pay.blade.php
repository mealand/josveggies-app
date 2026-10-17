<!DOCTYPE html>
<html lang="en">
<head><x-head title="Test payment" /></head>
<body class="grid min-h-dvh place-items-center bg-leaf-950 p-4">
    <div class="card w-full max-w-sm p-8 text-center">
        <p class="chip bg-pepper-300 text-ink">Local test gateway — no real money</p>
        <p class="mt-6 text-sm text-leaf-900/60">Order {{ $payment->order->number }}</p>
        <p class="font-display text-4xl font-semibold">{{ \App\Support\Money::format($payment->amount_kobo) }}</p>
        <form method="POST" action="{{ route('dev.fake-pay.complete', $payment->reference) }}" class="mt-8 grid gap-3">
            @csrf
            <button name="outcome" value="success" class="btn-primary w-full">Simulate successful payment</button>
            <button name="outcome" value="failed" class="btn-ghost w-full">Simulate failed payment</button>
        </form>
        <p class="mt-6 text-xs text-leaf-900/50">Set PAYMENT_GATEWAY=paystack with test keys to use the real Paystack checkout.</p>
    </div>
    @livewireScripts
</body>
</html>
