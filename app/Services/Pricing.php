<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class Pricing
{
    /**
     * Resolve the retail and charged price for a user/product pair.
     *
     * @return array{retail: float, charged: float, has_discount: bool}
     */
    public function priceFor(?User $user, Product $product): array
    {
        $retail = (float) $product->retail_price;

        if (! $user || ! $user->isB2B()) {
            return [
                'retail' => $retail,
                'charged' => $retail,
                'has_discount' => false,
            ];
        }

        $group = $user->customerGroup;

        $override = $product->groupPrices()
            ->where('customer_group_id', $group->id)
            ->first();

        if ($override) {
            $charged = (float) $override->price;
        } else {
            $charged = round($retail * (1 - ((float) $group->discount_percent / 100)), 2);
        }

        return [
            'retail' => $retail,
            'charged' => $charged,
            'has_discount' => $charged < $retail,
        ];
    }
}
