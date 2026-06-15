<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Support\Audience;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function category(Request $request, string $slug)
    {
        $audience = Audience::current();

        $category = Category::query()
            ->visibleTo($audience)
            ->where('slug', $slug)
            ->firstOrFail();

        // Products from this category AND every visible descendant, so a
        // top-level category page isn't near-empty when its items live in
        // subcategories.
        $categoryIds = $category->descendantAndSelfIds();

        $baseQuery = Product::query()
            ->visibleTo($audience)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));

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

        $sort = $this->normalizeSort($request->query('sort'));

        $products = $this->applySort($filtered, $sort)
            ->with(['categories', 'media', 'groupPrices', 'attributeValues.attribute'])
            ->paginate(24)
            ->withQueryString();

        $availableFilters = Attribute::query()
            ->where('is_filterable', true)
            ->whereHas('values.products', fn ($q) => $q
                ->visibleTo($audience)
                ->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)))
            ->with(['values' => function ($q) use ($categoryIds, $audience) {
                $q->whereHas('products', fn ($p) => $p
                    ->visibleTo($audience)
                    ->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)));
            }])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->get();

        // Sidebar category navigation: list the children of the current
        // category, or — if this is a leaf — the siblings under its parent.
        $navParent = $category->children()->visibleTo($audience)->exists()
            ? $category
            : $category->parent;

        $navCategories = $navParent
            ? $navParent->children()
                ->visibleTo($audience)
                ->withCount(['products as products_count' => fn ($q) => $q->visibleTo($audience)])
                ->orderBy('sort_order')
                ->orderBy('name_ka')
                ->get()
            : collect();

        return view('pages.category', [
            'category' => $category,
            'products' => $products,
            'availableFilters' => $availableFilters,
            'selectedAttrs' => $this->normalizeSelected($selectedAttrs),
            'priceFloor' => $priceFloor,
            'priceCeiling' => $priceCeiling,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'sort' => $sort,
            'ancestors' => $category->ancestors(),
            'navParent' => $navParent,
            'navCategories' => $navCategories,
        ]);
    }

    /** Whitelist the sort key so only known, safe options reach the query. */
    private function normalizeSort(mixed $sort): string
    {
        $allowed = ['default', 'price_asc', 'price_desc', 'name_asc', 'name_desc'];

        return in_array($sort, $allowed, true) ? $sort : 'default';
    }

    private function applySort($query, string $sort)
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('retail_price'),
            'price_desc' => $query->orderByDesc('retail_price'),
            'name_asc' => $query->orderBy('name_ka'),
            'name_desc' => $query->orderByDesc('name_ka'),
            default => $query->orderBy('sort_order')->orderBy('name_ka'),
        };
    }

    public function categories()
    {
        $audience = Audience::current();

        $roots = Category::query()
            ->visibleTo($audience)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q
                ->visibleTo($audience)
                ->withCount(['products as products_count' => fn ($p) => $p->visibleTo($audience)])
                ->orderBy('sort_order')
                ->orderBy('name_ka')])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->get();

        return view('pages.categories', compact('roots'));
    }

    public function product(string $slug)
    {
        $audience = Audience::current();

        $product = Product::query()
            ->visibleTo($audience)
            ->where('slug', $slug)
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
