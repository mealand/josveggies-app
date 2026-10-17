<x-layouts.console :title="$addOn->exists ? 'Edit '.$addOn->name : 'New add-on'">
    <x-catalogue-tabs />
    <form method="POST" action="{{ $addOn->exists ? route('console.catalogue.add-ons.update', $addOn) : route('console.catalogue.add-ons.store') }}" class="card max-w-xl space-y-5 p-6">
        @csrf
        @if ($addOn->exists) @method('PUT') @endif
        <x-field name="name" label="Display name" :value="$addOn->name" placeholder="e.g. Extra tomatoes" required />
        <div>
            <label for="item_id" class="label">Item</label>
            <select id="item_id" name="item_id" class="input" required>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected(old('item_id', $addOn->item_id) == $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <x-field name="quantity" label="Packs" type="number" min="1" :value="$addOn->quantity" required />
            <x-field name="price" label="Fixed price (₦)" type="number" step="0.01" min="1" :value="$addOn->exists ? \App\Support\Money::toNaira($addOn->price_kobo) : null" hint="Include a buffer for market-day price swings." required />
        </div>
        <x-field name="sort_order" label="Sort order" type="number" min="0" :value="$addOn->sort_order ?? 0" />
        <x-active-toggle :checked="$addOn->is_active ?? true" />
        <button class="btn-primary">Save add-on</button>
    </form>
</x-layouts.console>
