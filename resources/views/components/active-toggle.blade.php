@props(['checked' => true, 'label' => 'Active'])

<label class="flex items-center gap-3 text-sm font-semibold">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="size-5 rounded text-leaf-600" @checked(old('is_active', $checked))>
    {{ $label }}
</label>
