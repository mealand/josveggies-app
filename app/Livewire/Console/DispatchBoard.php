<?php

namespace App\Livewire\Console;

use App\Enums\Fulfilment;
use App\Enums\HandoverOutcome;
use App\Enums\OrderState;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Models\Handover;
use App\Models\Order;
use App\Models\Rider;
use App\Services\FulfilmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Dispatch orders by zone: assign a rider (handover log), then record delivered or failed.
 */
class DispatchBoard extends Component
{
    /** @var array<int, int|string|null> order id => rider id */
    public array $riderFor = [];

    public ?string $message = null;

    public function mount(): void
    {
        Gate::authorize('abuja-ops');
    }

    public function assign(int $orderId, FulfilmentService $fulfilment): void
    {
        Gate::authorize('abuja-ops');
        $riderId = (int) ($this->riderFor[$orderId] ?? 0);

        if ($riderId === 0) {
            $this->message = 'Choose a rider first.';

            return;
        }

        try {
            $handover = $fulfilment->assignRider(Order::query()->findOrFail($orderId), Rider::query()->findOrFail($riderId), auth()->user());
            $this->message = "Handed to {$handover->rider->name}.";
            unset($this->riderFor[$orderId]);
        } catch (BusinessRuleException $e) {
            $this->message = $e->getMessage();
        }
    }

    public function outcome(int $handoverId, string $outcome, FulfilmentService $fulfilment): void
    {
        Gate::authorize('abuja-ops');

        try {
            $fulfilment->recordOutcome(Handover::query()->findOrFail($handoverId), HandoverOutcome::from($outcome), auth()->user());
            $this->message = $outcome === 'delivered' ? 'Marked delivered.' : 'Marked failed — the order is back at the hub (same deadline).';
        } catch (BusinessRuleException|\ValueError $e) {
            $this->message = $e->getMessage();
        }
    }

    public function render(): View
    {
        $waiting = Order::query()
            ->where('fulfilment', Fulfilment::Dispatch)
            ->where('state', OrderState::ReadyForPickup)
            ->whereHas('run', fn ($q) => $q->where('state', RunState::Arrived))
            ->with(['user', 'dispatchZone', 'run'])
            ->get()
            ->groupBy(fn (Order $o) => $o->dispatchZone->name ?? 'No zone');

        $outForDelivery = Handover::query()
            ->where('outcome', HandoverOutcome::OutForDelivery)
            ->with(['order.user', 'order.dispatchZone', 'rider'])
            ->latest('handed_over_at')
            ->get();

        return view('livewire.console.dispatch-board', [
            'waiting' => $waiting,
            'outForDelivery' => $outForDelivery,
            'riders' => Rider::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
