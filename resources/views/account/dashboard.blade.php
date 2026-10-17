<x-layouts.account>
    <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
        <div class="space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}" class="card flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-xl" data-reveal>
                    <div class="grid size-14 shrink-0 place-items-center rounded-2xl bg-leaf-100"><x-veg-art class="w-11" /></div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-leaf-900">{{ $order->number }}</p>
                        <p class="text-sm text-leaf-900/55">Delivery {{ $order->run->delivery_starts_at->format('D j M') }} · {{ $order->fulfilment->label() }}</p>
                    </div>
                    <div class="text-right">
                        <x-order-state-badge :state="$order->state" />
                        <p class="mt-1 text-sm font-semibold">{{ \App\Support\Money::format($order->totalPaidKobo()) }}</p>
                    </div>
                </a>
            @empty
                <div class="card p-10 text-center">
                    <x-veg-art variant="salad" class="mx-auto w-36 animate-float" />
                    <p class="mt-6 font-display text-2xl text-leaf-900">No orders yet</p>
                    <a href="{{ route('home') }}#boxes" class="btn-primary mt-6">Reserve your first box</a>
                </div>
            @endforelse
            {{ $orders->links() }}
        </div>

        <aside class="space-y-4">
            <div class="relative overflow-hidden rounded-3xl bg-leaf-800 p-6 text-white">
                <div class="grain absolute inset-0"></div>
                <p class="relative text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">JosVeggies credit</p>
                <p class="relative mt-2 font-display text-4xl font-semibold">{{ \App\Support\Money::format($creditBalance) }}</p>
                <a href="{{ route('account.credit') }}" class="relative mt-4 inline-block text-sm font-semibold text-leaf-100 underline underline-offset-4">View history</a>
            </div>
            @unless (auth()->user()->hasVerifiedEmail())
                <div class="card p-5 text-sm">
                    <p class="font-semibold">Verify your email</p>
                    <p class="mt-1 text-leaf-900/60">You need a verified email to check out.</p>
                    <a href="{{ route('verification.notice') }}" class="link mt-2 inline-block">Resend link</a>
                </div>
            @endunless
        </aside>
    </div>
</x-layouts.account>
