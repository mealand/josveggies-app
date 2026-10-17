@php use App\Support\Money; @endphp

<div class="grid gap-6 xl:grid-cols-[1fr_1fr]">
    <div class="space-y-6">
        <form wire:submit="apply" class="card space-y-5 p-6">
            <div>
                <h2 class="font-sans text-lg font-bold">Swap an item</h2>
                <p class="text-sm text-leaf-900/60">Equal or greater value, like-for-like where possible, max 2 items per box (BR-11). Leave the substitute empty to credit the customer instead (BR-12).</p>
            </div>

            <div class="flex gap-2 rounded-full bg-cream-100 p-1 text-sm font-semibold">
                <label class="flex-1 cursor-pointer rounded-full px-4 py-2 text-center {{ $scope === 'box_type' ? 'bg-leaf-600 text-white' : '' }}">
                    <input type="radio" wire:model.live="scope" value="box_type" class="sr-only"> All boxes of a type
                </label>
                <label class="flex-1 cursor-pointer rounded-full px-4 py-2 text-center {{ $scope === 'order' ? 'bg-leaf-600 text-white' : '' }}">
                    <input type="radio" wire:model.live="scope" value="order" class="sr-only"> One order
                </label>
            </div>

            @if ($scope === 'box_type')
                <div>
                    <label class="label" for="boxTemplateId">Box type</label>
                    <select id="boxTemplateId" wire:model.live="boxTemplateId" class="input">
                        <option value="">Choose…</option>
                        @foreach ($this->boxTemplates as $box)
                            <option value="{{ $box->id }}">{{ $box->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="label" for="orderLineId">Order & box</label>
                    <select id="orderLineId" wire:model.live="orderLineId" class="input">
                        <option value="">Choose…</option>
                        @foreach ($this->boxLines as $line)
                            <option value="{{ $line->id }}">{{ $line->order->number }} · {{ $line->order->user->name }} · {{ $line->quantity }}× {{ $line->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="label" for="originalItemId">Unavailable item</label>
                <select id="originalItemId" wire:model.live="originalItemId" class="input">
                    <option value="">Choose…</option>
                    @foreach ($this->originalItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ Money::format($item->reference_price_kobo) }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="substituteItemId">Substitute (or leave empty to credit)</label>
                <select id="substituteItemId" wire:model="substituteItemId" class="input" @disabled(! $originalItemId)>
                    <option value="">No substitute — credit the customer</option>
                    @foreach ($this->substitutes as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ Money::format($item->reference_price_kobo) }})</option>
                    @endforeach
                </select>
                @error('substituteItemId') <p class="mt-1 text-sm text-tomato-600">{{ $message }}</p> @enderror
            </div>

            <button class="btn-primary w-full py-3.5" wire:loading.attr="disabled">Record substitution</button>
        </form>

        <form wire:submit="markAddOnUnavailable" class="card space-y-4 p-6">
            <h2 class="font-sans text-lg font-bold">Add-on unavailable</h2>
            <p class="text-sm text-leaf-900/60">Add-ons are not substituted — their full value is credited to every affected customer (BR-13).</p>
            <select wire:model="addOnId" class="input">
                <option value="">Choose add-on…</option>
                @foreach ($this->addOns as $addOn)
                    <option value="{{ $addOn->id }}">{{ $addOn->name }}</option>
                @endforeach
            </select>
            <button class="btn-ghost w-full">Credit all affected orders</button>
        </form>

        @if ($result)
            <div class="rounded-2xl bg-leaf-800 p-4 text-sm text-white">
                <p class="font-semibold">{{ $result }}</p>
                @foreach ($skipped as $skip)
                    <p class="mt-1 text-leaf-100/80">Skipped — {{ $skip }}</p>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card overflow-x-auto">
        <h2 class="px-5 pt-5 font-sans text-lg font-bold">Recorded in this Run</h2>
        <table class="mt-3 w-full min-w-[480px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr><th class="px-5 py-2.5">Order</th><th class="px-5 py-2.5">Change</th><th class="px-5 py-2.5 text-right">Credit</th></tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @forelse ($this->recorded as $sub)
                    <tr wire:key="sub-{{ $sub->id }}">
                        <td class="px-5 py-2.5 font-semibold">{{ $sub->order->number }}</td>
                        <td class="px-5 py-2.5">{{ $sub->originalItem->name }} → {{ $sub->substituteItem?->name ?? 'credited' }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $sub->credit_kobo ? Money::format($sub->credit_kobo) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-10 text-center text-leaf-900/55">No substitutions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
