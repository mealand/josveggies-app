@php $dt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : ''; @endphp

<x-layouts.console :title="$run->exists ? 'Edit '.$run->code : 'New Run'">
    <form method="POST" action="{{ $run->exists ? route('console.runs.update', $run) : route('console.runs.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_320px]">
        @csrf
        @if ($run->exists) @method('PUT') @endif

        <div class="space-y-6">
            <div class="card grid gap-5 p-6 sm:grid-cols-2">
                <x-field name="code" label="Run code" :value="$run->code" required />
                <div>
                    <label for="hub_id" class="label">Hub</label>
                    <select id="hub_id" name="hub_id" class="input" required>
                        @foreach ($hubs as $hub)
                            <option value="{{ $hub->id }}" @selected(old('hub_id', $run->hub_id) == $hub->id)>{{ $hub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="slot_capacity" label="Slot capacity (boxes)" type="number" min="1" :value="$run->slot_capacity" hint="Derived from car capacity using standard box sizes (BR-02)." required />
                <x-field name="break_even_boxes" label="Break-even boxes (estimate)" type="number" min="0" :value="$run->break_even_boxes" hint="Optional — used in reports." />
            </div>

            <div class="card grid gap-5 p-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <h2 class="font-sans text-lg font-bold">Timing</h2>
                    <p class="text-sm text-leaf-900/60">Window ≤ 3 days (BR-03). Orders must close at least 2 days before market day (BR-05) — vary the gap so customers can't infer the market day.</p>
                </div>
                <x-field name="opens_at" label="Orders open" type="datetime-local" :value="$dt(old('opens_at', $run->opens_at))" required />
                <x-field name="closes_at" label="Orders close" type="datetime-local" :value="$dt(old('closes_at', $run->closes_at))" required />
                <div class="rounded-2xl bg-tomato-500/5 p-4 ring-1 ring-tomato-500/20">
                    <x-field name="market_day" label="Market day (confidential)" type="date" :value="old('market_day', $run->market_day?->format('Y-m-d'))" hint="Visible only to Admin and Jos Ops." required />
                </div>
                <x-field name="delivery_starts_at" label="Pickup / delivery starts (shown to customers)" type="datetime-local" :value="$dt(old('delivery_starts_at', $run->delivery_starts_at))" required />
            </div>

            <div class="card p-6">
                <label for="internal_notes" class="label">Internal notes (farmer bookings, transport)</label>
                <textarea id="internal_notes" name="internal_notes" rows="4" class="input">{{ old('internal_notes', $run->internal_notes) }}</textarea>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="font-sans text-lg font-bold">Boxes offered</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($boxes as $box)
                        <label class="flex items-center gap-3 text-sm">
                            <input type="checkbox" name="box_template_ids[]" value="{{ $box->id }}" class="rounded text-leaf-600" @checked(in_array($box->id, old('box_template_ids', $selectedBoxes)))>
                            {{ $box->name }} <span class="text-leaf-900/50">{{ \App\Support\Money::format($box->price_kobo) }}</span>
                        </label>
                    @endforeach
                </div>
                <h2 class="mt-6 font-sans text-lg font-bold">Add-ons offered</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($addOns as $addOn)
                        <label class="flex items-center gap-3 text-sm">
                            <input type="checkbox" name="add_on_ids[]" value="{{ $addOn->id }}" class="rounded text-leaf-600" @checked(in_array($addOn->id, old('add_on_ids', $selectedAddOns)))>
                            {{ $addOn->name }} <span class="text-leaf-900/50">{{ \App\Support\Money::format($addOn->price_kobo) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <button class="btn-primary w-full">{{ $run->exists ? 'Save changes' : 'Create draft Run' }}</button>
        </div>
    </form>
</x-layouts.console>
