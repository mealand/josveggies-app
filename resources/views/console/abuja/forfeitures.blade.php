<x-layouts.console :title="'Forfeitures · '.$run->code">
    <p class="text-sm text-leaf-900/60">
        Orders not collected or delivered by {{ $run->pickup_ends_at?->format('D j M, g:ia') ?? 'the pickup deadline' }} are forfeited automatically, without refund (BR-16).
    </p>
    <div class="card mt-4 overflow-x-auto">
        <table class="w-full min-w-[520px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Fulfilment</th><th class="px-5 py-3">Forfeited</th></tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-5 py-3 font-semibold">{{ $order->number }}</td>
                        <td class="px-5 py-3">{{ $order->user->name }}</td>
                        <td class="px-5 py-3">{{ $order->fulfilment->label() }}</td>
                        <td class="px-5 py-3">{{ $order->forfeited_at?->format('D j M H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-leaf-900/55">No forfeited orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.console>
