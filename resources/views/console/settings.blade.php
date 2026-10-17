<x-layouts.console title="Settings">
    <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <form method="POST" action="{{ route('console.settings.update') }}" class="card h-fit space-y-4 p-6">
            @csrf
            @method('PUT')
            <h2 class="font-sans text-lg font-bold">Operations</h2>
            <x-field name="hold_minutes" label="Checkout slot hold (minutes)" type="number" min="5" max="30" :value="$settings['hold_minutes']" hint="BR-06" required />
            <x-field name="default_slot_capacity" label="Default slot capacity" type="number" min="1" :value="$settings['default_slot_capacity']" required />
            <div class="grid grid-cols-3 gap-3">
                <x-field name="morning_reminder_time" label="Morning reminder" type="time" :value="$settings['morning_reminder_time']" required />
                <x-field name="final_reminder_time" label="Final warning" type="time" :value="$settings['final_reminder_time']" required />
                <x-field name="pickup_deadline_time" label="Pickup deadline" type="time" :value="$settings['pickup_deadline_time']" required />
            </div>
            <x-field name="announcement" label="Storefront announcement (optional)" :value="$settings['announcement']" />
            <button class="btn-primary w-full">Save settings</button>
        </form>

        <div class="space-y-4">
            <h2 class="font-sans text-lg font-bold">Policies (Markdown)</h2>
            @foreach (\App\Models\Policy::SLUGS as $slug => $title)
                @php $policy = $policies[$slug]; @endphp
                <details class="card p-6" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer items-center justify-between font-semibold">
                        {{ $title }}
                        <span class="chip bg-leaf-100 text-leaf-800">{{ $policy ? 'v'.$policy->version : 'Not published' }}</span>
                    </summary>
                    <form method="POST" action="{{ route('console.policies.update', $slug) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <textarea name="body" rows="10" class="input font-mono text-xs">{{ old('body', $policy?->body) }}</textarea>
                        <div class="flex items-center justify-between">
                            <a href="{{ route('pages.policy', $slug) }}" target="_blank" class="link text-sm">View public page</a>
                            <button class="btn-primary btn-sm">Publish new version</button>
                        </div>
                    </form>
                </details>
            @endforeach
        </div>
    </div>
</x-layouts.console>
