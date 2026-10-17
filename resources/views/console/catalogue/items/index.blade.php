<x-layouts.console title="Catalogue">
    <x-slot:actions><a href="{{ route('console.catalogue.items.create') }}" class="btn-primary btn-sm">+ New item</a></x-slot:actions>
    <x-catalogue-tabs />
    <p class="mb-4 text-sm text-leaf-900/60">Reference prices are used to check substitutions are of equal or greater value and to calculate credit. Update them after farmer pre-booking.</p>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Pack unit</th><th class="px-5 py-3 text-right">Reference price</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @foreach ($items as $item)
                    <tr class="{{ $item->is_active ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3 font-semibold">{{ $item->name }}</td>
                        <td class="px-5 py-3">{{ \App\Models\Item::CATEGORIES[$item->category] ?? $item->category }}</td>
                        <td class="px-5 py-3">{{ $item->packUnit?->summary() ?? '—' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ \App\Support\Money::format($item->reference_price_kobo) }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('console.catalogue.items.edit', $item) }}" class="link">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.console>
