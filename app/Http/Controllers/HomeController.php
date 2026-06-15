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

        // Product count + a representative image per category (incl. subtree).
        foreach ($categories as $category) {
            $ids = $category->descendantAndSelfIds();
            $scope = fn ($q) => $q->whereIn('categories.id', $ids);

            $category->total_products = Product::query()
                ->visibleTo($audience)
                ->whereHas('categories', $scope)
                ->count();

            $rep = Product::query()
                ->visibleTo($audience)
                ->whereHas('categories', $scope)
                ->whereHas('media')
                ->with('media')
                ->latest('id')
                ->first();
            $category->image_url = $rep?->getFirstMediaUrl('images', 'thumb') ?: null;
        }

        $latestProducts = Product::query()
            ->visibleTo($audience)
            ->with(['categories', 'media', 'groupPrices', 'attributeValues.attribute'])
            ->latest('id')
            ->take(8)
            ->get();

        return view('pages.home', compact('categories', 'latestProducts'));
    }
}
