@php use App\Support\Money; use App\Enums\RunState; @endphp

<x-layouts.console :title="'P&L · '.$run->code">
    <x-slot:actions>
        <a href="{{ route('console.runs.show', $run) }}" class="btn-ghost btn-sm">Run details</a>
        @if ($run->state === RunState::PickupClosed)
            <form method="POST" action="{{ route('console.runs.transition', $run) }}" x-data="confirmSubmit" x-on:submit="check" data-message="Reconcile this Run? Make sure every cost has been recorded.">
                @csrf
                <input type="hidden" name="to" value="reconciled">
                <button class="btn-primary btn-sm">Reconcile Run</button>
            </form>
        @endif
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Revenue" :value="Money::format($pnl['revenue_kobo'])" tone="dark" :hint="'after '.Money::format($pnl['credit_issued_kobo']).' credit issued'" />
        <x-stat label="Costs" :value="Money::format($pnl['cost_total_kobo'])" />
        <x-stat label="Margin" :value="Money::format($pnl['margin_kobo'])" :hint="$pnl['margin_percent'] !== null ? $pnl['margin_percent'].'% of revenue' : null" />
        <x-stat label="Boxes sold" :value="$pnl['boxes_sold'].' / '.$run->slot_capacity" :hint="$pnl['break_even_boxes'] ? 'Break-even estimate: '.$pnl['break_even_boxes'] : 'Set a break-even estimate on the Run'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card p-6">
            <h2 class="font-sans text-lg font-bold">Breakdown</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt>Boxes</dt><dd>{{ Money::format($pnl['boxes_kobo']) }}</dd></div>
                <div class="flex justify-between"><dt>Add-ons</dt><dd>{{ Money::format($pnl['add_ons_kobo']) }}</dd></div>
                <div class="flex justify-between"><dt>Dispatch fees</dt><dd>{{ Money::format($pnl['dispatch_kobo']) }}</dd></div>
                <div class="flex justify-between text-tomato-600"><dt>Credit issued (substitutions / unavailable)</dt><dd>−{{ Money::format($pnl['credit_issued_kobo']) }}</dd></div>
                <div class="border-t border-leaf-900/10 pt-2"></div>
                @foreach ($pnl['costs'] as $category => $amount)
                    <div class="flex justify-between text-leaf-900/70"><dt>{{ ucfirst($category) }}</dt><dd>−{{ Money::format($amount) }}</dd></div>
                @endforeach
                <div class="flex justify-between border-t border-leaf-900/10 pt-2 font-bold"><dt>Margin</dt><dd class="{{ $pnl['margin_kobo'] < 0 ? 'text-tomato-600' : 'text-leaf-700' }}">{{ Money::format($pnl['margin_kobo']) }}</dd></div>
            </dl>
        </div>

        <div class="space-y-4">
            @if ($run->state !== RunState::Reconciled)
                <form method="POST" action="{{ route('console.reports.costs.store', $run) }}" class="card grid gap-3 p-6 sm:grid-cols-2">
                    @csrf
                    <h2 class="font-sans text-lg font-bold sm:col-span-2">Record a cost</h2>
                    <select name="category" class="input" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}">{{ $category->label() }}</option>
                        @endforeach
                    </select>
                    <input name="amount" type="number" step="0.01" min="0.01" class="input" placeholder="Amount ₦" required>
                    <input name="description" class="input sm:col-span-2" placeholder="Description (e.g. Cargo fee, escort)" required>
                    <button class="btn-primary sm:col-span-2">Add cost</button>
                </form>
            @endif
            <div class="card overflow-hidden">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-leaf-900/5">
                        @forelse ($costs as $cost)
                            <tr>
                                <td class="px-5 py-3"><p class="font-semibold">{{ $cost->description }}</p><p class="text-xs text-leaf-900/50">{{ $cost->category->label() }} · {{ $cost->recorder?->name }} · {{ $cost->created_at->format('j M H:i') }}</p></td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ Money::format($cost->amount_kobo) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-8 text-center text-leaf-900/55">No costs recorded yet (BR-21).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.console>
