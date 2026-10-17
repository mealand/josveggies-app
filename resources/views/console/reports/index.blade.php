@php use App\Support\Money; @endphp

<x-layouts.console title="Reports">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat label="Customers" :value="$customers['customers']" tone="dark" />
        <x-stat label="Returning" :value="$customers['returning']" :hint="$customers['customers'] ? round($customers['returning'] / $customers['customers'] * 100).'% of customers' : null" />
        <x-stat label="New (30 days)" :value="$customers['new_last_30_days']" />
        <x-stat label="Credit outstanding" :value="Money::format($customers['credit_outstanding_kobo'])" />
        <x-stat label="On waitlist" :value="$customers['waitlist_total']" hint="Unmet demand" />
    </div>

    <h2 class="mt-10 font-sans text-lg font-bold">Run performance</h2>
    <div class="card mt-3 overflow-x-auto">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr>
                    <th class="px-4 py-3">Run</th><th class="px-4 py-3">State</th><th class="px-4 py-3 text-right">Boxes / break-even</th>
                    <th class="px-4 py-3 text-right">Revenue</th><th class="px-4 py-3 text-right">Costs</th><th class="px-4 py-3 text-right">Margin</th>
                    <th class="px-4 py-3 text-right">Sell-out</th><th class="px-4 py-3 text-right">Waitlist</th><th class="px-4 py-3 text-right">Forfeit rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($overview as $row)
                    @php $pnl = $row['pnl']; @endphp
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('console.reports.run', $row['run']) }}" class="font-semibold text-leaf-800 hover:underline">{{ $row['run']->code }}</a></td>
                        <td class="px-4 py-3"><x-run-state-badge :state="$row['run']->state" /></td>
                        <td class="px-4 py-3 text-right tabular-nums {{ $pnl['break_even_boxes'] && $pnl['boxes_sold'] < $pnl['break_even_boxes'] ? 'text-tomato-600' : '' }}">{{ $pnl['boxes_sold'] }} / {{ $pnl['break_even_boxes'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Money::format($pnl['revenue_kobo']) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Money::format($pnl['cost_total_kobo']) }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $pnl['margin_kobo'] < 0 ? 'text-tomato-600' : 'text-leaf-700' }}">{{ Money::format($pnl['margin_kobo']) }}{{ $pnl['margin_percent'] !== null ? ' ('.$pnl['margin_percent'].'%)' : '' }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['sell_out_hours'] !== null ? $row['sell_out_hours'].'h' : $row['fill_percent'].'% filled' }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['waitlist'] }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['forfeiture_rate'] !== null ? $row['forfeiture_rate'].'%' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-leaf-900/55">No Runs to report yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-10 font-sans text-lg font-bold">Most substituted items</h2>
    <div class="card mt-3 max-w-2xl overflow-hidden">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($substitutions as $row)
                    <tr><td class="px-5 py-3 font-semibold">{{ $row->name }}</td><td class="px-5 py-3 text-right">{{ $row->times }}×</td><td class="px-5 py-3 text-right text-leaf-900/60">{{ Money::format((int) $row->credited_kobo) }} credited</td></tr>
                @empty
                    <tr><td class="px-5 py-8 text-center text-leaf-900/55">No substitutions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.console>
