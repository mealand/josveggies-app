<x-layouts.console title="Catalogue">
    <x-slot:actions><a href="{{ route('console.catalogue.add-ons.create') }}" class="btn-primary btn-sm">+ New add-on</a></x-slot:actions>
    <x-catalogue-tabs />

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[520px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr><th class="px-5 py-3">Add-on</th><th class="px-5 py-3">Contains</th><th class="px-5 py-3 text-right">Price</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($addOns as $addOn)
                    <tr class="{{ $addOn->is_active ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3 font-semibold">{{ $addOn->name }}</td>
                        <td class="px-5 py-3">{{ $addOn->quantity }} × {{ $addOn->item->name }} ({{ $addOn->item->packUnit?->traditional_name ?? 'unit' }})</td>
                        <td class="px-5 py-3 text-right">{{ \App\Support\Money::format($addOn->price_kobo) }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('console.catalogue.add-ons.edit', $addOn) }}" class="link">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-leaf-900/55">No add-ons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.console>
