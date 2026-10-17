<x-layouts.store :motion="true" title="Fresh from Jos, delivered to Abuja">
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-40 -left-32 size-[520px] rounded-full bg-leaf-200/50 blur-3xl" data-parallax="0.15"></div>
            <div class="absolute top-40 -right-40 size-[460px] rounded-full bg-pepper-300/40 blur-3xl" data-parallax="0.3"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pt-10 pb-20 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:px-8 lg:pt-20 lg:pb-28">
            <div>
                <p class="eyebrow" data-hero-fade>Jos → Abuja · fresh on market day</p>
                <h1 class="mt-5 text-[2.9rem] leading-[1.02] font-semibold text-leaf-900 sm:text-6xl lg:text-7xl">
                    <span class="block overflow-hidden pb-1"><span class="block" data-hero-line>Market-fresh</span></span>
                    <span class="block overflow-hidden pb-1"><span class="block" data-hero-line>from <em class="text-tomato-500 not-italic">Jos</em>,</span></span>
                    <span class="block overflow-hidden pb-2"><span class="block" data-hero-line>picked up in Abuja.</span></span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-leaf-900/70" data-hero-fade>
                    Pre-order a curated box in limited slots. We buy only what's ordered from pre-booked Plateau farmers,
                    pack it the same day and bring it to Abuja — no stale stock, no middlemen markup.
                </p>
                <div class="mt-8 flex flex-wrap gap-3" data-hero-fade>
                    <a href="#boxes" class="btn-primary px-7 py-3.5 text-base" data-magnetic>See this Run's boxes</a>
                    <a href="{{ route('pages.how') }}" class="btn-ghost px-7 py-3.5 text-base">How it works</a>
                </div>
                <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6" data-hero-fade>
                    <div>
                        <dt class="text-xs font-semibold text-leaf-900/50 uppercase">Farm to hub</dt>
                        <dd class="font-display text-3xl font-semibold text-leaf-900"><span data-count-to="1">1</span> day</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-leaf-900/50 uppercase">Unsold stock</dt>
                        <dd class="font-display text-3xl font-semibold text-leaf-900"><span data-count-to="0">0</span>%</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-leaf-900/50 uppercase">Box price</dt>
                        <dd class="font-display text-3xl font-semibold text-leaf-900">Fixed</dd>
                    </div>
                </dl>
            </div>

            <div class="relative">
                <div class="absolute -top-8 -left-6 w-24 animate-float" data-hero-veg><x-veg-art variant="pepper" /></div>
                <div class="absolute -right-4 -bottom-10 w-32 animate-float-slow" data-hero-veg><x-veg-art variant="salad" /></div>
                <x-run-banner :run="$run" :next-run="$nextRun" class="relative mx-auto max-w-md" data-hero-fade />
            </div>
        </div>
    </section>

    {{-- Produce marquee --}}
    <div class="relative overflow-hidden border-y border-leaf-900/10 bg-leaf-900 py-4 text-cream-100" aria-hidden="true">
        <div class="flex w-max animate-marquee gap-10 font-display text-2xl whitespace-nowrap italic">
            @foreach (array_merge($produce, $produce) as $name)
                <span class="flex items-center gap-10">{{ $name }} <span class="text-pepper-400 not-italic">✺</span></span>
            @endforeach
        </div>
    </div>

    {{-- Boxes --}}
    <section id="boxes" class="relative mx-auto max-w-7xl scroll-mt-20 px-4 py-24 sm:px-6 lg:px-8">
        <div class="max-w-2xl" data-reveal>
            <p class="eyebrow">This Run's range</p>
            <h2 class="mt-3 text-4xl font-semibold text-leaf-900 sm:text-5xl">Curated boxes, fixed prices.</h2>
            <p class="mt-4 text-lg text-leaf-900/65">
                Every box is packed to a standard, with traditional market units tied to real weights.
                If an item spikes or runs short on market day, we swap it for something of equal or greater value — or credit you.
            </p>
        </div>
        <div class="mt-12">
            <livewire:storefront.box-catalogue :run="$run" />
        </div>
    </section>

    {{-- How it works --}}
    <section class="relative overflow-hidden bg-cream-100 py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl" data-reveal>
                <p class="eyebrow">The Run</p>
                <h2 class="mt-3 text-4xl font-semibold text-leaf-900 sm:text-5xl">From a Jos market stall to your kitchen.</h2>
            </div>

            <div class="relative mt-16">
                <svg class="pointer-events-none absolute top-10 left-0 hidden h-24 w-full lg:block" viewBox="0 0 1200 100" preserveAspectRatio="none" aria-hidden="true">
                    <path data-draw d="M40 50 C 250 -10, 350 110, 600 50 S 950 -10, 1160 50" class="stroke-leaf-300" stroke-width="3" stroke-dasharray="8 10" fill="none"/>
                </svg>
                <ol class="relative grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $i => $step)
                        <li class="card relative p-6" data-reveal>
                            <span class="grid size-14 place-items-center rounded-2xl bg-leaf-600 font-display text-2xl font-semibold text-white shadow-lg shadow-leaf-900/20">{{ $i + 1 }}</span>
                            <h3 class="mt-5 text-xl font-semibold text-leaf-900">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-leaf-900/65">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Promise --}}
    <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-[var(--radius-blob)] bg-leaf-800 p-8 text-white lg:col-span-2" data-reveal>
                <div class="grain absolute inset-0"></div>
                <p class="eyebrow text-pepper-300!">Our promise</p>
                <h2 class="relative mt-3 max-w-xl text-3xl font-semibold sm:text-4xl">Fair substitutions, plain-language policies, credit you can see.</h2>
                <ul class="relative mt-8 grid gap-4 text-sm text-leaf-100/85 sm:grid-cols-2">
                    <li class="flex gap-3"><span class="text-pepper-300">●</span> Substitutes are always equal or greater value — at most two items per box.</li>
                    <li class="flex gap-3"><span class="text-pepper-300">●</span> If there's no fair swap, the item's value goes to your JosVeggies credit.</li>
                    <li class="flex gap-3"><span class="text-pepper-300">●</span> Your pickup code arrives by SMS and email — collect by noon the next day.</li>
                    <li class="flex gap-3"><span class="text-pepper-300">●</span> Every naira of credit is itemised in your account.</li>
                </ul>
            </div>
            <div class="card flex flex-col justify-between p-8" data-reveal>
                <div>
                    <p class="eyebrow">Pickup or dispatch</p>
                    <h3 class="mt-3 text-2xl font-semibold text-leaf-900">Collect free, or send a rider.</h3>
                    <ul class="mt-5 space-y-2 text-sm">
                        <li class="flex justify-between border-b border-leaf-900/5 pb-2"><span>Hub pickup</span><span class="font-semibold">Free</span></li>
                        @foreach ($zones as $zone)
                            <li class="flex justify-between border-b border-leaf-900/5 pb-2"><span>{{ $zone->name }}</span><span class="font-semibold">{{ \App\Support\Money::format($zone->fee_kobo) }}</span></li>
                        @endforeach
                    </ul>
                </div>
                @if ($hub)
                    <p class="mt-6 text-xs text-leaf-900/55">Hub: {{ $hub->name }} — {{ $hub->address }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- FAQ teaser --}}
    <section class="mx-auto max-w-3xl px-4 pb-8 sm:px-6">
        <h2 class="text-center text-4xl font-semibold text-leaf-900" data-reveal>Good to know</h2>
        <div class="mt-10 space-y-3">
            @foreach (array_slice($faqs, 0, 4) as $faq)
                <x-faq-item :question="$faq['q']" :answer="$faq['a']" data-reveal />
            @endforeach
        </div>
        <p class="mt-8 text-center"><a href="{{ route('pages.faq') }}" class="link">All questions →</a></p>
    </section>
</x-layouts.store>
