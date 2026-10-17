<x-layouts.store :motion="true" title="How it works">
    <x-page-hero eyebrow="How it works" title="One Run. One car. Zero waste."
                 lead="JosVeggies is a logistics business with a storefront on top. Here's how your pre-order becomes a fresh box in Abuja." />

    <section class="mx-auto max-w-4xl px-4 py-20 sm:px-6">
        <ol class="relative space-y-6 border-l-2 border-dashed border-leaf-300 pl-8">
            @foreach ($steps as $i => $step)
                <li class="relative" data-reveal>
                    <span class="absolute top-1 -left-[2.95rem] grid size-10 place-items-center rounded-full bg-leaf-600 font-display font-semibold text-white ring-8 ring-cream-50">{{ $i + 1 }}</span>
                    <div class="card p-6">
                        <h2 class="text-2xl font-semibold text-leaf-900">{{ $step['title'] }}</h2>
                        <p class="mt-2 text-leaf-900/70">{{ $step['body'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-16 grid gap-6 sm:grid-cols-2">
            <div class="card p-6" data-reveal>
                <p class="eyebrow">Pickup window</p>
                <h3 class="mt-2 text-xl font-semibold text-leaf-900">Arrival evening → 12 noon next day</h3>
                <p class="mt-2 text-sm text-leaf-900/65">The window opens when our Abuja team marks the Run as arrived, so delays never eat into your time. You'll get the arrival message, a ~9am reminder and an ~11am final warning.</p>
                @if ($hub)
                    <p class="mt-4 text-sm font-semibold text-leaf-900">{{ $hub->name }}</p>
                    <p class="text-sm text-leaf-900/65">{{ $hub->address }}</p>
                @endif
            </div>
            <div class="card p-6" data-reveal>
                <p class="eyebrow">Rider dispatch</p>
                <h3 class="mt-2 text-xl font-semibold text-leaf-900">Fixed fee per zone</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($zones as $zone)
                        <li class="flex justify-between border-b border-leaf-900/5 pb-2">
                            <span>{{ $zone->name }} @if ($zone->areas)<span class="text-leaf-900/45">— {{ $zone->areas }}</span>@endif</span>
                            <span class="font-semibold">{{ \App\Support\Money::format($zone->fee_kobo) }}</span>
                        </li>
                    @empty
                        <li class="text-leaf-900/60">Zones will be announced soon.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
</x-layouts.store>
