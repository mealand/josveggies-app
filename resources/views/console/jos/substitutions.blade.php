<x-layouts.console :title="'Substitutions · '.$run->code">
    <x-slot:actions>
        <a href="{{ route('console.jos.packing', $run) }}" class="btn-ghost btn-sm">Packing lists</a>
    </x-slot:actions>
    <livewire:console.substitution-manager :run="$run" />
</x-layouts.console>
