@php use App\Support\Money; @endphp

<div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1.4fr_1fr] lg:px-8">
    @if ($this->summary['lines'] === [])
        <div class="card p-10 text-center lg:col-span-2">
            <x-veg-art variant="leaf" class="mx-auto w-40" />
            <h1 class="mt-6 text-3xl font-semibold text-leaf-900">Your cart is empty</h1>
            <a href="{{ route('home') }}#boxes" class="btn-primary mt-6">Browse boxes</a>
        </div>
    @else
        <form wire:submit="placeOrder" class="space-y-6">
            <div>
                <p class="eyebrow">Checkout</p>
                <h1 class="mt-2 text-4xl font-semibold text-leaf-900">Almost there.</h1>
                @if ($this->run)
                    <p class="mt-2 text-leaf-900/60">Delivery {{ $this->run->delivery_starts_at->format('l j M') }} · orders close {{ $this->run->closes_at->format('D j M, g:ia') }}</p>
                @endif
            </div>

            @if ($errorMessage)
                <div class="rounded-2xl bg-tomato-500/10 px-4 py-3 text-sm font-medium text-tomato-600" role="alert">{{ $errorMessage }}</div>
            @endif

            {{-- Fulfilment --}}
            <fieldset class="card p-6">
                <legend class="sr-only">How would you like to receive your order?</legend>
                <h2 class="font-sans text-lg font-bold text-leaf-900">1. Pickup or dispatch</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="relative flex cursor-pointer flex-col rounded-2xl border-2 p-4 transition {{ $fulfilment === 'pickup' ? 'border-leaf-600 bg-leaf-50' : 'border-leaf-900/10 hover:border-leaf-300' }}">
                        <input type="radio" wire:model.live="fulfilment" value="pickup" class="sr-only">
                        <span class="font-semibold text-leaf-900">Pickup at hub</span>
                        <span class="text-sm text-leaf-900/60">Free · collect by noon the day after arrival</span>
                    </label>
                    <label class="relative flex cursor-pointer flex-col rounded-2xl border-2 p-4 transition {{ $fulfilment === 'dispatch' ? 'border-leaf-600 bg-leaf-50' : 'border-leaf-900/10 hover:border-leaf-300' }}">
                        <input type="radio" wire:model.live="fulfilment" value="dispatch" class="sr-only">
                        <span class="font-semibold text-leaf-900">Rider dispatch</span>
                        <span class="text-sm text-leaf-900/60">Fixed fee per zone</span>
                    </label>
                </div>

                @if ($fulfilment === 'dispatch')
                    <div class="mt-5 grid gap-4" wire:transition>
                        <div>
                            <label for="zone" class="label">Delivery zone</label>
                            <select id="zone" wire:model.live="dispatchZoneId" class="input">
                                <option value="">Choose a zone…</option>
                                @foreach ($this->zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }} — {{ Money::format($zone->fee_kobo) }}{{ $zone->areas ? ' ('.$zone->areas.')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('dispatchZoneId') <p class="mt-1 text-sm text-tomato-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="address" class="label">Delivery address</label>
                            <textarea id="address" wire:model="deliveryAddress" rows="3" class="input" placeholder="House number, street, estate, landmark"></textarea>
                            @error('deliveryAddress') <p class="mt-1 text-sm text-tomato-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
            </fieldset>

            {{-- Contact --}}
            <div class="card p-6">
                <h2 class="font-sans text-lg font-bold text-leaf-900">2. Contact</h2>
                <label for="phone" class="label mt-4">Phone number (for your pickup code)</label>
                <input id="phone" type="tel" inputmode="tel" autocomplete="tel" wire:model="phone" class="input" placeholder="0803 123 4567">
                @error('phone') <p class="mt-1 text-sm text-tomato-600">{{ $message }}</p> @enderror
            </div>

            {{-- Policies (BR-10) --}}
            <div class="card p-6">
                <h2 class="font-sans text-lg font-bold text-leaf-900">3. Our policies</h2>
                <div class="mt-4 space-y-4">
                    <label class="flex gap-3 text-sm">
                        <input type="checkbox" wire:model="acceptForfeiture" class="mt-0.5 size-5 shrink-0 rounded border-leaf-900/30 text-leaf-600 focus:ring-leaf-500">
                        <span>I understand that orders <strong>not collected by 12 noon the day after arrival are forfeited without refund</strong>.
                            <a href="{{ route('pages.policy', 'forfeiture') }}" target="_blank" class="link">Read the policy</a></span>
                    </label>
                    @error('acceptForfeiture') <p class="text-sm text-tomato-600">{{ $message }}</p> @enderror
                    <label class="flex gap-3 text-sm">
                        <input type="checkbox" wire:model="acceptSubstitution" class="mt-0.5 size-5 shrink-0 rounded border-leaf-900/30 text-leaf-600 focus:ring-leaf-500">
                        <span>I accept that box contents <strong>may be substituted with items of equal or greater value</strong> (max 2 per box), or credited if no substitute is available.
                            <a href="{{ route('pages.policy', 'substitution') }}" target="_blank" class="link">Read the policy</a></span>
                    </label>
                    @error('acceptSubstitution') <p class="text-sm text-tomato-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="btn-accent w-full py-4 text-base lg:hidden" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="placeOrder">Pay {{ Money::format($this->total) }}</span>
                <span wire:loading wire:target="placeOrder">Reserving your slot…</span>
            </button>
        </form>

        {{-- Summary --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card overflow-hidden">
                <div class="bg-leaf-900 px-6 py-5 text-white">
                    <p class="text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">Order summary</p>
                    <p class="mt-1 text-sm text-leaf-100/70">{{ $this->summary['box_count'] }} {{ Str::plural('slot', $this->summary['box_count']) }} will be held for {{ \App\Models\Setting::get('hold_minutes') }} minutes while you pay.</p>
                </div>
                <ul class="divide-y divide-leaf-900/5 px-6">
                    @foreach ($this->summary['lines'] as $line)
                        <li class="flex justify-between gap-4 py-3 text-sm">
                            <span>{{ $line['quantity'] }} × {{ $line['name'] }}</span>
                            <span class="font-semibold">{{ Money::format($line['line_total_kobo']) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-leaf-900/10 px-6 py-4 text-sm">
                    <div class="flex justify-between"><dt class="text-leaf-900/60">Subtotal</dt><dd>{{ Money::format($this->summary['subtotal_kobo']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-leaf-900/60">{{ $fulfilment === 'dispatch' ? 'Dispatch fee' : 'Pickup' }}</dt><dd>{{ $fulfilment === 'dispatch' ? Money::format($this->dispatchFee) : 'Free' }}</dd></div>
                    @if ($this->creditBalance > 0)
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-leaf-900/60">
                                <input type="checkbox" wire:model.live="useCredit" class="size-4 rounded border-leaf-900/30 text-leaf-600">
                                Use credit ({{ Money::format($this->creditBalance) }})
                            </label>
                            <dd class="text-leaf-700">−{{ Money::format($this->creditApplied) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-baseline justify-between border-t border-leaf-900/10 pt-3">
                        <dt class="font-semibold">To pay</dt>
                        <dd class="font-display text-3xl font-semibold">{{ Money::format($this->total) }}</dd>
                    </div>
                </dl>
                <div class="hidden px-6 pb-6 lg:block">
                    <button type="button" wire:click="placeOrder" class="btn-accent w-full py-4 text-base" wire:loading.attr="disabled" data-magnetic>
                        <span wire:loading.remove wire:target="placeOrder">Pay {{ Money::format($this->total) }} securely</span>
                        <span wire:loading wire:target="placeOrder">Reserving your slot…</span>
                    </button>
                    <p class="mt-3 text-center text-xs text-leaf-900/50">Card, bank transfer or USSD via Paystack. Your order is confirmed once payment is verified.</p>
                </div>
            </div>
        </aside>
    @endif
</div>
