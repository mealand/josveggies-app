@props(['state'])

@php
    $tone = match ($state) {
        \App\Enums\OrderState::PendingPayment, \App\Enums\OrderState::Expired => 'bg-pepper-300/40 text-leaf-950',
        \App\Enums\OrderState::Collected, \App\Enums\OrderState::Delivered => 'bg-leaf-600 text-white',
        \App\Enums\OrderState::ReadyForPickup, \App\Enums\OrderState::OutForDelivery => 'bg-tomato-500 text-white',
        \App\Enums\OrderState::Forfeited, \App\Enums\OrderState::Cancelled => 'bg-leaf-950/10 text-leaf-950/60',
        default => 'bg-leaf-100 text-leaf-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "chip $tone"]) }}>{{ $state->label() }}</span>
