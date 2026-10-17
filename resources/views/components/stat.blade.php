@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

<div {{ $attributes->merge(['class' => 'card p-5 '.($tone === 'dark' ? 'bg-leaf-900! text-white' : '')]) }}>
    <p class="text-xs font-semibold tracking-wider uppercase {{ $tone === 'dark' ? 'text-leaf-200' : 'text-leaf-900/50' }}">{{ $label }}</p>
    <p class="mt-2 font-display text-3xl font-semibold tabular-nums">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs {{ $tone === 'dark' ? 'text-leaf-200/80' : 'text-leaf-900/50' }}">{{ $hint }}</p>
    @endif
</div>
