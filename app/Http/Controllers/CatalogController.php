<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class CatalogController extends Controller
{
    public function category(string $slug)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = Product::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->with(['media', 'groupPrices'])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->paginate(24);

        return view('pages.category', compact('category', 'products'));
    }

    public function product(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'media', 'groupPrices'])
            ->firstOrFail();

        return view('pages.product', compact('product'));
    }
}
