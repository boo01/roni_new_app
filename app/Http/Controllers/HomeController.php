<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function show()
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->get();

        $latestProducts = Product::query()
            ->where('is_active', true)
            ->with(['categories', 'media', 'groupPrices'])
            ->latest('id')
            ->take(8)
            ->get();

        return view('pages.home', compact('categories', 'latestProducts'));
    }
}
