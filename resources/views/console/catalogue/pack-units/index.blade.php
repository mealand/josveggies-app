<x-layouts.console title="Catalogue">
    <x-slot:actions><a href="{{ route('console.catalogue.pack-units.create') }}" class="btn-primary btn-sm">+ New pack unit</a></x-slot:actions>
    <x-catalogue-tabs />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($packUnits as $unit)
            <a href="{{ route('console.catalogue.pack-units.edit', $unit) }}" class="card p-5 transition hover:-translate-y-0.5">
                <p class="font-display text-xl font-semibold">{{ $unit->traditional_name }}</p>
                <p class="text-sm text-leaf-900/60">{{ $unit->summary() }}</p>
                <p class="mt-2 text-xs text-leaf-900/45">Used by {{ $unit->items_count }} {{ Str::plural('item', $unit->items_count) }}</p>
            </a>
        @empty
            <div class="card p-8 text-sm text-leaf-900/60">No pack units yet.</div>
        @endforelse
    </div>
</x-layouts.console>
