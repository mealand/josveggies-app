<?php

namespace App\Livewire\Storefront;

use App\Services\Cart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartButton extends Component
{
    public int $count = 0;

    public function mount(Cart $cart): void
    {
        $this->count = $cart->itemCount();
    }

    #[On('cart-updated')]
    public function refreshCount(Cart $cart): void
    {
        $this->count = $cart->itemCount();
    }

    public function render(): View
    {
        return view('livewire.storefront.cart-button');
    }
}
