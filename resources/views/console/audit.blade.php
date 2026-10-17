<x-layouts.console title="Audit log">
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('console.audit') }}" class="chip {{ $current === '' ? 'bg-leaf-600 text-white' : 'bg-white ring-1 ring-leaf-900/10' }}">All</a>
        @foreach ($logs as $log)
            <a href="{{ route('console.audit', ['log' => $log]) }}" class="chip {{ $current === $log ? 'bg-leaf-600 text-white' : 'bg-white ring-1 ring-leaf-900/10' }}">{{ $log }}</a>
        @endforeach
    </div>
    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-cream-100 text-left text-xs tracking-wider text-leaf-900/60 uppercase">
                <tr><th class="px-5 py-3">When</th><th class="px-5 py-3">Who</th><th class="px-5 py-3">Area</th><th class="px-5 py-3">What</th></tr>
            </thead>
            <tbody class="divide-y divide-leaf-900/5">
                @foreach ($activities as $activity)
                    <tr>
                        <td class="px-5 py-2.5 whitespace-nowrap text-leaf-900/60">{{ $activity->created_at->format('j M H:i:s') }}</td>
                        <td class="px-5 py-2.5">{{ $activity->causer?->name ?? 'System' }}</td>
                        <td class="px-5 py-2.5"><span class="chip bg-leaf-100 text-leaf-800">{{ $activity->log_name }}</span></td>
                        <td class="px-5 py-2.5">{{ $activity->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $activities->links() }}</div>
</x-layouts.console>
