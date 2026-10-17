<x-layouts.console :title="'Procurement · '.$run->code">
    <x-slot:actions>
        <a href="{{ route('console.jos.procurement.pdf', $run) }}" class="btn-primary btn-sm">Download PDF</a>
        <a href="{{ route('console.runs.show', $run) }}" class="btn-ghost btn-sm">Back to Run</a>
    </x-slot:actions>

    <p class="text-sm text-leaf-900/60">
        Totals from {{ $orderCount }} paid {{ Str::plural('order', $orderCount) }} · market day <strong class="text-tomato-600">{{ $run->market_day->format('l j M') }}</strong>.
        Substitutions and unavailable add-ons are already applied.
    </p>

    <div class="card mt-4 overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Category</th><th class="px-5 py-3 text-right">Quantity</th><th class="px-5 py-3 text-right">Est. cost</th></tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($items as $row)
                    <tr>
                        <td class="px-5 py-3 font-semibold">{{ $row['name'] }}</td>
                        <td class="px-5 py-3 text-leaf-900/60">{{ \App\Models\Item::CATEGORIES[$row['category']] ?? $row['category'] }}</td>
                        <td class="px-5 py-3 text-right text-lg font-bold tabular-nums">{{ $row['quantity'] }} <span class="text-sm font-normal text-leaf-900/55">{{ $row['pack'] ?? 'units' }}</span></td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ \App\Support\Money::format($row['estimated_cost_kobo']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-leaf-900/55">No paid orders yet.</td></tr>
                @endforelse
            </tbody>
            @if ($items->isNotEmpty())
                <tfoot class="border-t-2 border-leaf-900/10">
                    <tr><td colspan="3" class="px-5 py-3 text-right font-semibold">Estimated total</td><td class="px-5 py-3 text-right font-bold">{{ \App\Support\Money::format($items->sum('estimated_cost_kobo')) }}</td></tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-layouts.console>
