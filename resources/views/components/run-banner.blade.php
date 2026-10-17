{{--
    Public Run status card. BR-19: only uses customer-safe fields — never market_day.
--}}
@props(['run' => null, 'nextRun' => null])

@php
    $state = match (true) {
        $run === null => 'none',
        $run->state === \App\Enums\RunState::Scheduled => 'scheduled',
        $run->state === \App\Enums\RunState::Open && $run->isAcceptingOrders() => 'open',
        $run->state === \App\Enums\RunState::Open => 'full',
        default => 'closed',
    };
    $remaining = $run?->slotsRemaining() ?? 0;
@endphp

<div {{ $attributes->merge(['class' => 'card relative overflow-hidden p-6 sm:p-7']) }}>
    <div class="absolute -top-16 -right-16 size-48 rounded-full bg-pepper-300/40 blur-3xl"></div>

    <div class="relative flex items-center gap-2">
        <span class="relative flex size-2.5">
            @if ($state === 'open')
                <span class="absolute inline-flex size-full animate-pulse-ring rounded-full bg-leaf-500"></span>
            @endif
            <span class="relative inline-flex size-2.5 rounded-full {{ $state === 'open' ? 'bg-leaf-500' : ($state === 'scheduled' ? 'bg-pepper-500' : 'bg-tomato-500') }}"></span>
        </span>
        <p class="text-xs font-bold tracking-[0.16em] uppercase {{ $state === 'open' ? 'text-leaf-700' : 'text-tomato-600' }}">
            @switch($state)
                @case('open') Orders open @break
                @case('scheduled') Opening soon @break
                @case('full') Full — join waitlist @break
                @case('closed') Orders closed @break
                @default Next Run coming soon
            @endswitch
        </p>
    </div>

    @if ($run)
        <p class="relative mt-3 text-sm text-leaf-900/60">Next delivery</p>
        <p class="relative font-display text-3xl font-semibold text-leaf-900 sm:text-4xl">{{ $run->delivery_starts_at->format('l j M') }}</p>
        <p class="relative mt-1 text-sm text-leaf-900/60">Pickup from {{ $run->delivery_starts_at->format('g:ia') }} at our Abuja hub · rider delivery available</p>

        @if (in_array($state, ['open', 'full'], true))
            <div class="relative mt-6">
                <div class="flex items-baseline justify-between text-sm">
                    <span class="font-semibold text-leaf-900"><span data-count-to="{{ $remaining }}">{{ $remaining }}</span> of {{ $run->slot_capacity }} slots left</span>
                    <span class="text-leaf-900/50">{{ $run->fillPercent() }}% booked</span>
                </div>
                <div class="mt-2 h-3 overflow-hidden rounded-full bg-leaf-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-leaf-500 to-leaf-600" data-meter="{{ $run->fillPercent() }}" style="width: {{ $run->fillPercent() }}%"></div>
                </div>
            </div>

            <div x-data="countdown" data-until="{{ $run->closes_at->toIso8601String() }}" class="relative mt-6 grid grid-cols-4 gap-2 text-center">
                @foreach (['days' => 'Days', 'hours' => 'Hrs', 'minutes' => 'Min', 'seconds' => 'Sec'] as $unit => $label)
                    <div class="rounded-2xl bg-leaf-900 px-2 py-3 text-white">
                        <p class="font-display text-2xl font-semibold tabular-nums" x-text="{{ $unit }}">--</p>
                        <p class="text-[10px] font-semibold tracking-widest text-leaf-200 uppercase">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
            <p class="relative mt-2 text-center text-xs text-leaf-900/50">Orders close {{ $run->closes_at->format('D j M, g:ia') }}</p>
        @elseif ($state === 'scheduled')
            <div x-data="countdown" data-until="{{ $run->opens_at->toIso8601String() }}" class="relative mt-6 rounded-2xl bg-leaf-900 p-4 text-white">
                <p class="text-xs text-leaf-200">Pre-orders open in</p>
                <p class="font-display text-2xl font-semibold tabular-nums" x-text="label">{{ $run->opens_at->diffForHumans() }}</p>
                <p class="mt-1 text-xs text-leaf-200">{{ $run->opens_at->format('D j M, g:ia') }} · {{ $run->slot_capacity }} slots only</p>
            </div>
        @endif
    @else
        <p class="relative mt-3 font-display text-2xl font-semibold text-leaf-900">We're planning the next Run</p>
        <p class="relative mt-1 text-sm text-leaf-900/60">Join the waitlist and we'll text you the moment pre-orders open.</p>
    @endif

    <div class="relative mt-6">
        @if ($state === 'open')
            <a href="#boxes" class="btn-accent w-full" data-magnetic>Reserve your box →</a>
        @else
            <livewire:storefront.join-waitlist :run="$run" />
            @if ($state === 'closed' && $nextRun)
                <p class="mt-3 text-xs text-leaf-900/60">Next Run: orders open {{ $nextRun->opens_at->format('D j M') }}.</p>
            @endif
        @endif
    </div>
</div>
