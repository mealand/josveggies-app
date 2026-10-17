<x-layouts.console title="Hub & zones">
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="space-y-4">
            <h2 class="font-sans text-lg font-bold">Pickup hub</h2>
            @foreach ($hubs as $hub)
                <form method="POST" action="{{ route('console.hubs.update', $hub) }}" class="card space-y-4 p-6">
                    @csrf
                    @method('PUT')
                    <x-field name="name" label="Hub name" :value="$hub->name" required />
                    <div>
                        <label class="label" for="address-{{ $hub->id }}">Address</label>
                        <textarea id="address-{{ $hub->id }}" name="address" rows="2" class="input" required>{{ old('address', $hub->address) }}</textarea>
                    </div>
                    <div>
                        <label class="label" for="directions-{{ $hub->id }}">Directions / landmark</label>
                        <textarea id="directions-{{ $hub->id }}" name="directions" rows="2" class="input">{{ old('directions', $hub->directions) }}</textarea>
                    </div>
                    <x-field name="opening_note" label="Opening note" :value="$hub->opening_note" placeholder="e.g. Lit and staffed through the night" />
                    <button class="btn-primary">Save hub</button>
                </form>
            @endforeach
        </section>

        <section class="space-y-4">
            <h2 class="font-sans text-lg font-bold">Dispatch zones (fixed fee per zone)</h2>
            @foreach ($zones as $zone)
                <form method="POST" action="{{ route('console.zones.update', $zone) }}" class="card grid gap-3 p-4 sm:grid-cols-[1fr_1fr_120px_auto]">
                    @csrf
                    @method('PUT')
                    <input name="name" value="{{ $zone->name }}" class="input" aria-label="Zone name" required>
                    <input name="areas" value="{{ $zone->areas }}" class="input" aria-label="Areas" placeholder="Areas covered">
                    <input name="fee" type="number" step="0.01" min="0" value="{{ \App\Support\Money::toNaira($zone->fee_kobo) }}" class="input" aria-label="Fee (₦)" required>
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($zone->is_active) class="rounded text-leaf-600"> Active</label>
                        <button class="btn-ghost btn-sm">Save</button>
                    </div>
                </form>
            @endforeach

            <form method="POST" action="{{ route('console.zones.store') }}" class="card grid gap-3 border-2 border-dashed border-leaf-300 p-4 sm:grid-cols-[1fr_1fr_120px_auto]">
                @csrf
                <input type="hidden" name="is_active" value="1">
                <input name="name" class="input" placeholder="New zone name" required>
                <input name="areas" class="input" placeholder="Areas covered">
                <input name="fee" type="number" step="0.01" min="0" class="input" placeholder="Fee ₦" required>
                <button class="btn-primary btn-sm">Add zone</button>
            </form>
        </section>
    </div>
</x-layouts.console>
