<x-layouts.console :title="$packUnit->exists ? 'Edit '.$packUnit->traditional_name : 'New pack unit'">
    <x-catalogue-tabs />
    <form method="POST" enctype="multipart/form-data" action="{{ $packUnit->exists ? route('console.catalogue.pack-units.update', $packUnit) : route('console.catalogue.pack-units.store') }}" class="card max-w-xl space-y-5 p-6">
        @csrf
        @if ($packUnit->exists) @method('PUT') @endif
        <x-field name="traditional_name" label="Traditional name" :value="$packUnit->traditional_name" placeholder="e.g. 1 Mudu, Bunch, Paint bucket" required />
        <x-field name="container" label="Standard container" :value="$packUnit->container" placeholder="e.g. 10 × 10 cm box" required />
        <div class="grid grid-cols-2 gap-4">
            <x-field name="min_weight_g" label="Min weight (g)" type="number" min="1" :value="$packUnit->min_weight_g" />
            <x-field name="max_weight_g" label="Max weight (g)" type="number" min="1" :value="$packUnit->max_weight_g" />
        </div>
        <div>
            <label for="photo" class="label">Photo of the actual pack</label>
            <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input">
        </div>
        <button class="btn-primary">Save pack unit</button>
    </form>
</x-layouts.console>
