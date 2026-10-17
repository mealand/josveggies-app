<x-layouts.console :title="'Packing lists · '.$run->code">
    <x-slot:actions>
        <a href="{{ route('console.jos.packing.pdf', $run) }}" class="btn-primary btn-sm">Packing PDF</a>
        <a href="{{ route('console.jos.labels.pdf', $run) }}" class="btn-ghost btn-sm">Labels PDF</a>
        <a href="{{ route('console.jos.substitutions', $run) }}" class="btn-ghost btn-sm">Substitutions</a>
    </x-slot:actions>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $row)
            @php $order = $row['order']; @endphp
            <div class="card p-5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-display text-xl font-semibold">{{ $order->number }}</p>
                        <p class="text-sm text-leaf-900/60">{{ $order->user->name }}</p>
                    </div>
                    <span class="chip {{ $order->fulfilment->value === 'dispatch' ? 'bg-tomato-500 text-white' : 'bg-leaf-600 text-white' }}">
                        {{ $order->fulfilment->value === 'dispatch' ? 'DISPATCH · '.$order->dispatchZone?->name : 'PICKUP' }}
                    </span>
                </div>
                <ul class="mt-4 space-y-1.5 text-sm">
                    @foreach ($row['contents'] as $item)
                        <li class="flex items-start gap-2 {{ $item['unavailable'] ? 'text-leaf-900/40 line-through' : '' }}">
                            <span class="mt-0.5 size-4 shrink-0 rounded border border-leaf-900/30"></span>
                            <span><strong>{{ $item['quantity'] }}×</strong> {{ $item['name'] }} <span class="text-leaf-900/45">({{ $item['line'] }})</span>
                                @if ($item['note'] && ! $item['unavailable'])<span class="chip ml-1 bg-pepper-300/50">{{ $item['note'] }}</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="card p-10 text-center text-leaf-900/55 md:col-span-3">No paid orders yet.</div>
        @endforelse
    </div>
</x-layouts.console>
