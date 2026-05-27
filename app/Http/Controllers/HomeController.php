<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Audience;

class HomeController extends Controller
{
    public function show()
    {
        $audience = Audience::current();

        $categories = Category::query()
            ->visibleTo($audience)
            ->where('show_in_header', true)
            ->orderBy('header_sort_order')
            ->orderBy('name_ka')
            ->get();

        // Product count per category, including everything in its subtree.
        foreach ($categories as $category) {
            $ids = $category->descendantAndSelfIds();
            $category->total_products = Product::query()
                ->visibleTo($audience)
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids))
                ->count();
        }

        $latestProducts = Product::query()
            ->visibleTo($audience)
            ->with(['categories', 'media', 'groupPrices'])
            ->latest('id')
            ->take(8)
            ->get();

        return view('pages.home', compact('categories', 'latestProducts'));
    }
}
