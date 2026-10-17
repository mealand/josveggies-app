<?php

namespace App\Livewire\Storefront;

use App\Enums\OrderState;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Shown when the customer returns from the gateway. It never confirms anything itself:
 * it asks the gateway server-to-server (verifyAndApply) and polls until the order changes.
 */
class PaymentStatus extends Component
{
    #[Locked]
    public string $reference;

    public int $checks = 0;

    public function mount(string $reference): void
    {
        $payment = Payment::query()->with('order')->where('reference', $reference)->firstOrFail();
        abort_unless($payment->order->user_id === Auth::id(), 404);
        $this->reference = $reference;
        $this->check();
    }

    public function check(): void
    {
        $this->checks++;

        if ($this->checks > 40) {
            return;
        }

        try {
            app(PaymentService::class)->verifyAndApply($this->reference);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function render(): View
    {
        $payment = Payment::query()->with('order')->where('reference', $this->reference)->firstOrFail();

        return view('livewire.storefront.payment-status', [
            'payment' => $payment,
            'order' => $payment->order,
            'confirmed' => in_array($payment->order->state, OrderState::paid(), true),
        ]);
    }
}
