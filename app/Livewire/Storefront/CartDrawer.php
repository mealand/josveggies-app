<?php

namespace App\Livewire\Storefront;

use App\Models\Run;
use App\Services\Cart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Re-render with the latest session cart.
    }

    public function increment(string $type, int $id, Cart $cart): void
    {
        $this->change($type, $id, 1, $cart);
    }

    public function decrement(string $type, int $id, Cart $cart): void
    {
        $this->change($type, $id, -1, $cart);
    }

    public function remove(string $type, int $id, Cart $cart): void
    {
        $run = $this->currentRun($cart);
        if ($run !== null && in_array($type, ['box', 'add_on'], true)) {
            $cart->setQuantity($run, $type, $id, 0);
            $this->dispatch('cart-updated');
        }
    }

    private function change(string $type, int $id, int $delta, Cart $cart): void
    {
        $run = $this->currentRun($cart);

        if ($run === null || ! in_array($type, ['box', 'add_on'], true)) {
            return;
        }

        $bucket = $type === 'box' ? 'boxes' : 'add_ons';
        $current = $cart->raw()[$bucket][$id] ?? 0;
        $cart->setQuantity($run, $type, $id, $current + $delta);
        $this->dispatch('cart-updated');
    }

    private function currentRun(Cart $cart): ?Run
    {
        $runId = $cart->raw()['run_id'];

        return $runId ? Run::query()->find($runId) : null;
    }

    public function render(): View
    {
        $cart = app(Cart::class);
        $run = $this->currentRun($cart);

        return view('livewire.storefront.cart-drawer', [
            'run' => $run,
            'summary' => $cart->summary($run),
        ]);
    }
}
