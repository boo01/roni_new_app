<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function category(Request $request, string $slug)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $baseQuery = Product::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->where('is_active', true);

        $priceRange = (clone $baseQuery)->selectRaw('MIN(retail_price) as min, MAX(retail_price) as max')->first();
        $priceFloor = (float) ($priceRange->min ?? 0);
        $priceCeiling = (float) ($priceRange->max ?? 0);

        $selectedAttrs = (array) $request->query('attr', []);
        $priceMin = $request->filled('price_min') ? (float) $request->query('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->query('price_max') : null;

        $filtered = (clone $baseQuery)
            ->when($priceMin !== null, fn ($q) => $q->where('retail_price', '>=', $priceMin))
            ->when($priceMax !== null, fn ($q) => $q->where('retail_price', '<=', $priceMax));

        foreach ($selectedAttrs as $attrSlug => $values) {
            if (! is_string($attrSlug) || empty($values)) {
                continue;
            }
            $valueSlugs = is_array($values)
                ? array_values(array_filter($values))
                : array_values(array_filter(explode(',', (string) $values)));
            if ($valueSlugs === []) {
                continue;
            }
            $filtered->whereHas('attributeValues', function ($q) use ($attrSlug, $valueSlugs) {
                $q->whereIn('attribute_values.slug', $valueSlugs)
                    ->whereHas('attribute', fn ($a) => $a->where('slug', $attrSlug));
            });
        }

        $products = $filtered
            ->with(['categories', 'media', 'groupPrices'])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->paginate(24)
            ->withQueryString();

        $availableFilters = Attribute::query()
            ->where('is_filterable', true)
            ->whereHas('values.products', fn ($q) => $q->whereHas(
                'categories',
                fn ($c) => $c->where('categories.id', $category->id)
            ))
            ->with(['values' => function ($q) use ($category) {
                $q->whereHas(
                    'products',
                    fn ($p) => $p->whereHas('categories', fn ($c) => $c->where('categories.id', $category->id))
                );
            }])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->get();

        return view('pages.category', [
            'category' => $category,
            'products' => $products,
            'availableFilters' => $availableFilters,
            'selectedAttrs' => $this->normalizeSelected($selectedAttrs),
            'priceFloor' => $priceFloor,
            'priceCeiling' => $priceCeiling,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
        ]);
    }

    public function product(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories', 'media', 'groupPrices', 'attributeValues.attribute'])
            ->firstOrFail();

        return view('pages.product', compact('product'));
    }

    /** @return array<string, array<int,string>> */
    private function normalizeSelected(array $raw): array
    {
        $out = [];
        foreach ($raw as $attrSlug => $values) {
            if (! is_string($attrSlug) || empty($values)) {
                continue;
            }
            $out[$attrSlug] = is_array($values)
                ? array_values(array_filter($values))
                : array_values(array_filter(explode(',', (string) $values)));
        }
        return $out;
    }
}
