@props(['state'])

@php
    $tone = match ($state) {
        \App\Enums\RunState::Open => 'bg-leaf-600 text-white',
        \App\Enums\RunState::Draft, \App\Enums\RunState::Cancelled => 'bg-leaf-950/10 text-leaf-950/60',
        \App\Enums\RunState::Scheduled => 'bg-pepper-300/50 text-leaf-950',
        \App\Enums\RunState::Arrived => 'bg-tomato-500 text-white',
        \App\Enums\RunState::Reconciled => 'bg-leaf-900 text-white',
        default => 'bg-leaf-100 text-leaf-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "chip $tone"]) }}>{{ $state->label() }}</span>
