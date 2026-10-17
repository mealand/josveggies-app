<x-layouts.account title="Credit">
    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
        <div class="relative h-fit overflow-hidden rounded-3xl bg-leaf-800 p-6 text-white">
            <div class="grain absolute inset-0"></div>
            <p class="relative text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Balance</p>
            <p class="relative mt-2 font-display text-4xl font-semibold">{{ \App\Support\Money::format($balance) }}</p>
            <p class="relative mt-3 text-xs text-leaf-100/70">Credit is applied at checkout. It can't be topped up or withdrawn.</p>
        </div>
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                    <tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Details</th><th class="px-5 py-3 text-right">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-leaf-900/5">
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="px-5 py-3 whitespace-nowrap text-leaf-900/60">{{ $entry->created_at->format('j M Y') }}</td>
                            <td class="px-5 py-3">{{ $entry->reason->label() }}@if ($entry->description)<span class="text-leaf-900/50"> — {{ $entry->description }}</span>@endif</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $entry->amount_kobo > 0 ? 'text-leaf-700' : 'text-tomato-600' }}">{{ $entry->amount_kobo > 0 ? '+' : '' }}{{ \App\Support\Money::format($entry->amount_kobo) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-leaf-900/55">No credit activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $entries->links() }}</div>
        </div>
    </div>
</x-layouts.account>
