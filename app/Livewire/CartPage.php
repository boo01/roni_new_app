<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Component;

class CartPage extends Component
{
    public function updateQuantity(Cart $cart, string $lineKey, int $quantity): void
    {
        $cart->setQuantity($lineKey, max(0, $quantity));
        $this->dispatch('cart-updated', count: $cart->totalQuantity());
    }

    public function remove(Cart $cart, string $lineKey): void
    {
        $cart->remove($lineKey);
        $this->dispatch('cart-updated', count: $cart->totalQuantity());
    }

    public function render(Cart $cart)
    {
        return view('livewire.cart-page', [
            'summary' => $cart->summary(auth()->user()),
        ]);
    }
}
