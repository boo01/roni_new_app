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
            ->with(['children' => fn ($q) => $q->visibleTo($audience)->orderBy('sort_order')->orderBy('name_ka')])
            ->orderBy('header_sort_order')
            ->orderBy('name_ka')
            ->get();

        $latestProducts = Product::query()
            ->visibleTo($audience)
            ->with(['categories', 'media', 'groupPrices'])
            ->latest('id')
            ->take(8)
            ->get();

        return view('pages.home', compact('categories', 'latestProducts'));
    }
}
