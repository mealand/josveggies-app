<x-layouts.console :title="$box->exists ? 'Edit '.$box->name : 'New box'">
    <x-catalogue-tabs />

    <form method="POST" enctype="multipart/form-data" action="{{ $box->exists ? route('console.catalogue.boxes.update', $box) : route('console.catalogue.boxes.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_1fr]">
        @csrf
        @if ($box->exists) @method('PUT') @endif

        <div class="card space-y-5 p-6">
            <x-field name="name" label="Box name" :value="$box->name" required />
            <x-field name="tagline" label="Tagline" :value="$box->tagline" placeholder="e.g. Everything for a pot of stew" />
            <div>
                <label for="description" class="label">Description</label>
                <textarea id="description" name="description" rows="3" class="input">{{ old('description', $box->description) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-field name="price" label="Price (₦)" type="number" step="0.01" min="1" :value="$box->exists ? \App\Support\Money::toNaira($box->price_kobo) : null" required />
                <x-field name="sort_order" label="Sort order" type="number" min="0" :value="$box->sort_order ?? 0" />
            </div>
            <div>
                <label for="accent" class="label">Colour accent</label>
                <select id="accent" name="accent" class="input">
                    @foreach ($accents as $key => $label)
                        <option value="{{ $key }}" @selected(old('accent', $box->accent) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="photo" class="label">Photo of the actual box (optional)</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input">
                <p class="mt-1 text-xs text-leaf-900/50">Converted to a compressed WebP; metadata is stripped.</p>
            </div>
            <x-active-toggle :checked="$box->is_active ?? true" />
        </div>

        <div class="card p-6">
            <h2 class="font-sans text-lg font-bold">Contents (per box)</h2>
            <p class="text-sm text-leaf-900/60">Quantities are in each item's pack unit. Changes apply to new orders only.</p>
            <div class="mt-4 max-h-[520px] divide-y divide-leaf-900/5 overflow-y-auto">
                @foreach ($items as $item)
                    <label class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span>{{ $item->name }} <span class="text-leaf-900/45">· {{ $item->packUnit?->traditional_name ?? 'unit' }} · {{ \App\Support\Money::format($item->reference_price_kobo) }}</span></span>
                        <input type="number" min="0" max="50" name="contents[{{ $item->id }}]" value="{{ old('contents.'.$item->id, $quantities[$item->id] ?? 0) }}" class="input w-20 py-1.5 text-center">
                    </label>
                @endforeach
            </div>
            <button class="btn-primary mt-6 w-full">{{ $box->exists ? 'Save box' : 'Create box' }}</button>
        </div>
    </form>
</x-layouts.console>
