<?php

namespace App\Livewire\Console;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Services\FulfilmentService;
use App\Services\ProcurementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Hub pickup verification: type or scan the code, check the order, mark Collected.
 */
class PickupDesk extends Component
{
    public string $code = '';

    #[Locked]
    public ?int $orderId = null;

    public ?string $message = null;

    public bool $success = false;

    public function mount(): void
    {
        Gate::authorize('abuja-ops');
    }

    #[On('pickup-code-scanned')]
    public function scanned(string $code, FulfilmentService $fulfilment): void
    {
        $this->code = $code;
        $this->lookup($fulfilment);
    }

    public function lookup(FulfilmentService $fulfilment): void
    {
        Gate::authorize('abuja-ops');
        $this->reset(['orderId', 'message', 'success']);

        // Rate-limit guessing of pickup codes.
        $key = 'pickup-verify:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->message = 'Too many lookups. Wait a minute.';

            return;
        }
        RateLimiter::hit($key);

        $order = $fulfilment->findByPickupCode($this->code);

        if ($order === null) {
            $this->message = 'No order with that code is waiting at the hub.';
            activity('pickups')->causedBy(auth()->user())->withProperties(['result' => 'not_found'])->log('pickup.lookup');

            return;
        }

        $this->orderId = $order->id;
    }

    public function collect(FulfilmentService $fulfilment): void
    {
        Gate::authorize('abuja-ops');
        $order = Order::query()->findOrFail($this->orderId);

        try {
            $fulfilment->markCollected($order, auth()->user());
            $this->success = true;
            $this->message = "{$order->number} collected. Enjoy!";
            $this->code = '';
            $this->orderId = null;
        } catch (BusinessRuleException $e) {
            $this->message = $e->getMessage();
        }
    }

    public function clear(): void
    {
        $this->reset(['code', 'orderId', 'message', 'success']);
    }

    public function render(): View
    {
        $procurement = app(ProcurementService::class);
        $order = $this->orderId ? Order::query()->with(['user', 'run', 'dispatchZone'])->find($this->orderId) : null;

        return view('livewire.console.pickup-desk', [
            'order' => $order,
            'contents' => $order ? $procurement->packedContents($order) : [],
        ]);
    }
}
