<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function add(Request $request, Cart $cart, Product $product)
    {
        $request->validate([
            'quantity' => 'sometimes|integer|min:1|max:999',
            'options' => 'sometimes|array',
            'options.*' => 'nullable|integer',
        ]);

        $options = $this->resolveOptions($product, (array) $request->input('options', []));

        $cart->add($product->id, (int) $request->input('quantity', 1), $options);

        if ($request->expectsJson()) {
            return response()->json([
                'count' => $cart->totalQuantity(),
                'message' => 'პროდუქცია დაემატა კალათში',
            ]);
        }

        return back(fallback: route('cart.show'))->with('status', 'პროდუქცია დაემატა კალათში');
    }

    /**
     * Validate the submitted option choices against what the product actually
     * offers, and snapshot human-readable labels for the cart/order.
     *
     * @param  array<int|string, mixed>  $selected  attribute_id => attribute_value_id
     * @return array<int, array{attribute_id:int,attribute_name:string,attribute_slug:?string,value_id:int,value_name:string,value_slug:?string}>
     *
     * @throws ValidationException
     */
    private function resolveOptions(Product $product, array $selected): array
    {
        $resolved = [];
        $errors = [];

        foreach ($product->selectableOptionGroups() as $group) {
            $attribute = $group['attribute'];
            $chosenId = (int) ($selected[$attribute->id] ?? 0);

            if ($chosenId <= 0) {
                if ($attribute->is_required) {
                    $errors["options.{$attribute->id}"] = "გთხოვთ აირჩიოთ: {$attribute->name_ka}";
                }

                continue;
            }

            $value = $group['values']->firstWhere('id', $chosenId);
            if (! $value) {
                $errors["options.{$attribute->id}"] = "არასწორი არჩევანი: {$attribute->name_ka}";

                continue;
            }

            $resolved[] = [
                'attribute_id' => (int) $attribute->id,
                'attribute_name' => $attribute->name_ka,
                'attribute_slug' => $attribute->slug,
                'value_id' => (int) $value->id,
                'value_name' => $value->value_ka,
                'value_slug' => $value->slug,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $resolved;
    }
}
