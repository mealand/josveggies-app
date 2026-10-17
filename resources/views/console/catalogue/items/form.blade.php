<x-layouts.console :title="$item->exists ? 'Edit '.$item->name : 'New item'">
    <x-catalogue-tabs />
    <form method="POST" action="{{ $item->exists ? route('console.catalogue.items.update', $item) : route('console.catalogue.items.store') }}" class="card max-w-xl space-y-5 p-6">
        @csrf
        @if ($item->exists) @method('PUT') @endif
        <x-field name="name" label="Name" :value="$item->name" required />
        <div>
            <label for="category" class="label">Category (for like-for-like substitutions)</label>
            <select id="category" name="category" class="input">
                @foreach (\App\Models\Item::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $item->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="pack_unit_id" class="label">Pack unit</label>
            <select id="pack_unit_id" name="pack_unit_id" class="input">
                <option value="">None</option>
                @foreach ($packUnits as $unit)
                    <option value="{{ $unit->id }}" @selected(old('pack_unit_id', $item->pack_unit_id) == $unit->id)>{{ $unit->summary() }}</option>
                @endforeach
            </select>
        </div>
        <x-field name="reference_price" label="Reference price per pack (₦)" type="number" step="0.01" min="0" :value="$item->exists ? \App\Support\Money::toNaira($item->reference_price_kobo) : null" required />
        <x-active-toggle :checked="$item->is_active ?? true" />
        <button class="btn-primary">Save item</button>
    </form>
</x-layouts.console>
