<nav class="mb-6 flex gap-1 overflow-x-auto rounded-full bg-white p-1 ring-1 ring-leaf-900/5 [scrollbar-width:none]" aria-label="Catalogue">
    @foreach (['boxes' => 'Boxes', 'items' => 'Items & prices', 'pack-units' => 'Pack units', 'add-ons' => 'Add-ons'] as $key => $label)
        <a href="{{ route('console.catalogue.'.$key.'.index') }}"
           class="shrink-0 rounded-full px-5 py-2.5 text-sm font-semibold transition {{ request()->routeIs('console.catalogue.'.$key.'.*') ? 'bg-leaf-600 text-white' : 'text-leaf-900/70 hover:bg-leaf-50' }}">{{ $label }}</a>
    @endforeach
</nav>
