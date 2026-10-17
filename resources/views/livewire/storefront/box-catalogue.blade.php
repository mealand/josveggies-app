@php
    $accepting = $this->run?->isAcceptingOrders() ?? false;
    $accentBg = ['tomato' => 'bg-tomato-500/10', 'leaf' => 'bg-leaf-200/60', 'salad' => 'bg-pepper-300/30', 'pepper' => 'bg-tomato-400/15'];
@endphp

<div>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->boxes as $box)
            <article wire:key="box-{{ $box->id }}" data-reveal data-tilt
                     class="card group flex flex-col overflow-hidden will-change-transform">
                <div class="relative overflow-hidden {{ $accentBg[$box->accent] ?? 'bg-leaf-100' }} px-8 pt-8 pb-2">
                    <div class="absolute -top-10 -right-10 size-40 rounded-full bg-white/40 blur-2xl transition duration-700 group-hover:scale-125"></div>
                    @if ($box->photo_path)
                        <img src="{{ Storage::url($box->photo_path) }}" alt="{{ $box->name }}" loading="lazy" decoding="async" class="relative mx-auto aspect-[4/3] w-full rounded-2xl object-cover">
                    @else
                        <x-veg-art :variant="$box->accent" class="relative mx-auto max-w-[260px] transition duration-700 ease-out group-hover:scale-105 group-hover:-rotate-2" />
                    @endif
                </div>

                <div class="flex flex-1 flex-col p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-semibold text-leaf-900">{{ $box->name }}</h3>
                            @if ($box->tagline)
                                <p class="mt-1 text-sm text-leaf-900/60">{{ $box->tagline }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 rounded-full bg-leaf-900 px-3 py-1.5 font-display text-lg font-semibold text-white">
                            {{ \App\Support\Money::format($box->price_kobo) }}
                        </p>
                    </div>

                    <ul class="mt-5 flex flex-wrap gap-1.5" aria-label="Contents">
                        @foreach ($box->items as $item)
                            <li class="chip bg-cream-100 text-leaf-900/80 ring-1 ring-leaf-900/5">
                                <span class="font-bold text-leaf-700">{{ $item->pivot->quantity }}×</span>
                                {{ $item->name }}
                                @if ($item->packUnit)
                                    <span class="text-leaf-900/45">· {{ $item->packUnit->traditional_name }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-auto flex items-center justify-between gap-3 pt-6">
                        <p class="text-xs font-medium text-leaf-900/50">1 box = 1 slot</p>
                        @if ($accepting)
                            <div x-data="addToCart">
                                <button type="button" wire:click="addBox({{ $box->id }})" x-on:click="fly" data-magnetic
                                        class="btn-primary" wire:loading.attr="disabled" wire:target="addBox({{ $box->id }})">
                                    <span x-text="buttonLabel">Add to order</span>
                                </button>
                            </div>
                        @else
                            <span class="chip bg-leaf-900/5 text-leaf-900/60">Opens with the next Run</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="text-leaf-900/60">Our box range is being refreshed — check back soon.</p>
        @endforelse
    </div>

    @if ($this->addOns->isNotEmpty())
        <div class="mt-16" data-reveal>
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Top up your box</p>
                    <h3 class="mt-2 text-3xl font-semibold text-leaf-900">Add-ons</h3>
                    <p class="mt-1 text-sm text-leaf-900/60">Standard packs, sold alongside a box. Add-ons don't use a slot.</p>
                </div>
            </div>
            <div class="mt-6 flex snap-x gap-4 overflow-x-auto pb-4 [scrollbar-width:none]">
                @foreach ($this->addOns as $addOn)
                    <div wire:key="addon-{{ $addOn->id }}" class="card flex w-60 shrink-0 snap-start flex-col p-5">
                        <x-veg-art variant="pepper" class="mx-auto w-28" />
                        <p class="mt-3 font-semibold text-leaf-900">{{ $addOn->name }}</p>
                        <p class="text-xs text-leaf-900/55">{{ $addOn->quantity }} × {{ $addOn->item->packUnit?->summary() ?? $addOn->item->name }}</p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="font-display text-lg font-semibold">{{ \App\Support\Money::format($addOn->price_kobo) }}</span>
                            @if ($accepting)
                                <div x-data="addToCart">
                                    <button type="button" wire:click="addAddOn({{ $addOn->id }})" x-on:click="fly" class="btn-ghost btn-sm" aria-label="Add {{ $addOn->name }}">
                                        + Add
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
