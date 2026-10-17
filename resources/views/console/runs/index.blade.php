<x-layouts.console title="Runs">
    <x-slot:actions>
        @can('admin')
            <a href="{{ route('console.runs.create') }}" class="btn-primary btn-sm">+ New Run</a>
        @endcan
    </x-slot:actions>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr>
                    <th class="px-5 py-3">Run</th>
                    <th class="px-5 py-3">State</th>
                    <th class="px-5 py-3">Window</th>
                    @can('view-market-day')<th class="px-5 py-3">Market day</th>@endcan
                    <th class="px-5 py-3">Delivery</th>
                    <th class="px-5 py-3">Slots</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($runs as $run)
                    <tr class="transition hover:bg-leaf-50/60">
                        <td class="px-5 py-3"><a href="{{ route('console.runs.show', $run) }}" class="font-semibold text-leaf-800 hover:underline">{{ $run->code }}</a></td>
                        <td class="px-5 py-3"><x-run-state-badge :state="$run->state" /></td>
                        <td class="px-5 py-3 text-leaf-900/70">{{ $run->opens_at->format('j M H:i') }} → {{ $run->closes_at->format('j M H:i') }}</td>
                        @can('view-market-day')<td class="px-5 py-3 font-semibold">{{ $run->market_day->format('D j M') }}</td>@endcan
                        <td class="px-5 py-3">{{ $run->delivery_starts_at->format('D j M H:i') }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-leaf-100"><div class="h-full bg-leaf-600" style="width: {{ $run->fillPercent() }}%"></div></div>
                                <span class="tabular-nums">{{ $run->slots_confirmed }}/{{ $run->slot_capacity }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-leaf-900/55">No Runs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $runs->links() }}</div>
</x-layouts.console>
