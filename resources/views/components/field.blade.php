@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'bag' => 'default'])

<div>
    <label for="{{ $name }}" class="label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           {{ $attributes->merge(['class' => 'input']) }}
           @error($name, $bag) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @if ($hint)
        <p class="mt-1 text-xs text-leaf-900/50">{{ $hint }}</p>
    @endif
    @error($name, $bag)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-tomato-600">{{ $message }}</p>
    @enderror
</div>
