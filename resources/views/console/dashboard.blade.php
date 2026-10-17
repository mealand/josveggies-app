<x-layouts.console title="Dashboard">
    <div class="relative overflow-hidden rounded-3xl bg-leaf-900 p-6 text-white sm:p-8">
        <div class="grain absolute inset-0"></div>
        <div class="absolute -top-20 -right-10 size-72 rounded-full bg-tomato-500/25 blur-3xl"></div>
        <p class="relative text-xs font-bold tracking-[0.18em] text-pepper-300 uppercase">{{ auth()->user()->role->label() }}</p>
        <p class="relative mt-2 font-display text-3xl font-semibold sm:text-4xl">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ str(auth()->user()->name)->before(' ') }}.</p>
        <p class="relative mt-1 text-leaf-100/70">{{ now()->format('l j F Y, g:ia') }} WAT</p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Active Runs" :value="$activeRuns->count()" />
        @can('admin')<x-stat label="Draft Runs" :value="$drafts" />@endcan
        @can('abuja-ops')
            <x-stat label="Ready at hub" :value="$readyForPickup" hint="Awaiting collection or rider" />
            <x-stat label="Out for delivery" :value="$outForDelivery" />
        @endcan
    </div>

    @if ($arrivedRun)
        @can('abuja-ops')
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-tomato-500 p-6 text-white">
                <div>
                    <p class="font-display text-2xl font-semibold">Pickup window is open</p>
                    <p class="text-white/80">Run {{ $arrivedRun->code }} · ends {{ $arrivedRun->pickup_ends_at->format('D j M, g:ia') }}</p>
                </div>
                <a href="{{ route('console.abuja.pickup') }}" class="btn bg-white text-tomato-600 hover:bg-cream-100">Open pickup desk →</a>
            </div>
        @endcan
    @endif

    <h2 class="mt-10 font-sans text-lg font-bold">Runs in progress</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($activeRuns as $run)
            <a href="{{ route('console.runs.show', $run) }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <p class="font-semibold">{{ $run->code }}</p>
                    <x-run-state-badge :state="$run->state" />
                </div>
                <p class="mt-3 text-sm text-leaf-900/60">Delivery {{ $run->delivery_starts_at->format('D j M') }}</p>
                @can('view-market-day')
                    <p class="text-sm text-tomato-600">Market day {{ $run->market_day->format('D j M') }}</p>
                @endcan
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-leaf-100"><div class="h-full bg-leaf-600" style="width: {{ $run->fillPercent() }}%"></div></div>
                <p class="mt-2 text-xs text-leaf-900/55">{{ $run->slots_confirmed }} of {{ $run->slot_capacity }} slots confirmed</p>
            </a>
        @empty
            <div class="card p-8 text-sm text-leaf-900/60 md:col-span-2">
                No active Runs.
                @can('admin') <a href="{{ route('console.runs.create') }}" class="link">Create one →</a> @endcan
            </div>
        @endforelse
    </div>
</x-layouts.console>
