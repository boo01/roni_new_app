<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Component;

class CartPage extends Component
{
    public array $quantities = [];

    public function mount(Cart $cart): void
    {
        $this->quantities = $cart->raw();
    }

    public function update(Cart $cart, int $productId, int $quantity): void
    {
        $cart->set($productId, max(0, $quantity));
        $this->quantities = $cart->raw();
    }

    public function remove(Cart $cart, int $productId): void
    {
        $cart->remove($productId);
        $this->quantities = $cart->raw();
    }

    public function render(Cart $cart)
    {
        $summary = $cart->summary(auth()->user());

        return view('livewire.cart-page', [
            'summary' => $summary,
        ]);
    }
}
