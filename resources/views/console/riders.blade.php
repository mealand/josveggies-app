<x-layouts.console title="Riders">
    <div class="max-w-4xl space-y-3">
        @foreach ($riders as $rider)
            <form method="POST" action="{{ route('console.riders.update', $rider) }}" class="card grid gap-3 p-4 sm:grid-cols-[1fr_1fr_1fr_auto]">
                @csrf
                @method('PUT')
                <input name="name" value="{{ $rider->name }}" class="input" aria-label="Name" required>
                <input name="phone" value="{{ $rider->phone }}" class="input" aria-label="Phone" required>
                <input name="company" value="{{ $rider->company }}" class="input" aria-label="Company" placeholder="Company">
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($rider->is_active) class="rounded text-leaf-600"> Active</label>
                    <button class="btn-ghost btn-sm">Save</button>
                </div>
                <p class="text-xs text-leaf-900/45 sm:col-span-4">{{ $rider->handovers_count }} handovers recorded</p>
            </form>
        @endforeach

        <form method="POST" action="{{ route('console.riders.store') }}" class="card grid gap-3 border-2 border-dashed border-leaf-300 p-4 sm:grid-cols-[1fr_1fr_1fr_auto]">
            @csrf
            <input type="hidden" name="is_active" value="1">
            <input name="name" class="input" placeholder="Rider name" required>
            <input name="phone" class="input" placeholder="Phone" required>
            <input name="company" class="input" placeholder="Company (optional)">
            <button class="btn-primary btn-sm">Add rider</button>
        </form>
    </div>
</x-layouts.console>
