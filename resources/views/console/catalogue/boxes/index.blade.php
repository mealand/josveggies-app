<x-layouts.console title="Catalogue">
    <x-slot:actions><a href="{{ route('console.catalogue.boxes.create') }}" class="btn-primary btn-sm">+ New box</a></x-slot:actions>
    <x-catalogue-tabs />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($boxes as $box)
            <a href="{{ route('console.catalogue.boxes.edit', $box) }}" class="card flex gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-xl">
                <div class="grid size-20 shrink-0 place-items-center rounded-2xl bg-leaf-50">
                    @if ($box->photo_path)
                        <img src="{{ Storage::url($box->photo_path) }}" alt="" class="size-20 rounded-2xl object-cover">
                    @else
                        <x-veg-art :variant="$box->accent" class="w-16" />
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="font-semibold">{{ $box->name }}</p>
                    <p class="text-sm text-leaf-900/60">{{ \App\Support\Money::format($box->price_kobo) }} · {{ $box->items_count }} items</p>
                    @unless ($box->is_active)<span class="chip mt-2 bg-leaf-950/10">Inactive</span>@endunless
                </div>
            </a>
        @empty
            <div class="card p-8 text-sm text-leaf-900/60">No boxes yet.</div>
        @endforelse
    </div>
</x-layouts.console>
