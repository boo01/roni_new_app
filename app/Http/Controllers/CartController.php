<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request, Cart $cart, Product $product): RedirectResponse
    {
        $request->validate(['quantity' => 'sometimes|integer|min:1|max:999']);
        $cart->add($product->id, (int) $request->input('quantity', 1));
        return back(fallback: route('cart.show'))->with('status', 'პროდუქცია დაემატა კალათში');
    }
}
