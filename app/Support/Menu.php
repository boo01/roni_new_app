<?php

namespace App\Support;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Support\Collection;

class Menu
{
    /**
     * The header menu as a list of nodes:
     *   ['label' => string, 'url' => string, 'target' => ?string, 'children' => array]
     *
     * Falls back to the top-level categories when no menu is configured,
     * so the header is never empty.
     *
     * @return Collection<int, array>
     */
    public static function header(string $audience): Collection
    {
        $items = MenuItem::query()
            ->where('location', 'header')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'page', 'category',
                'children' => fn ($q) => $q->where('is_active', true)->with(['page', 'category']),
            ])
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return self::fallbackFromCategories($audience);
        }

        return $items
            ->map(fn (MenuItem $item) => self::node($item, $audience))
            ->filter()
            ->values();
    }

    private static function node(MenuItem $item, string $audience): ?array
    {
        // Hide items whose target is hidden for this audience or gone.
        if ($item->type === 'category') {
            $cat = $item->category;
            if (! $cat || ! $cat->is_active || ! $cat->{'visible_to_' . $audience}) {
                return null;
            }
        } elseif ($item->type === 'page' && (! $item->page || ! $item->page->is_published)) {
            return null;
        }

        $url = $item->resolveUrl();
        $label = $item->resolveLabel();
        if ($url === null || blank($label)) {
            return null;
        }

        $children = $item->children
            ->map(fn (MenuItem $child) => self::node($child, $audience))
            ->filter()
            ->values()
            ->all();

        return [
            'label' => $label,
            'url' => $url,
            'target' => $item->target_blank ? '_blank' : null,
            'children' => $children,
        ];
    }

    /** @return Collection<int, array> */
    private static function fallbackFromCategories(string $audience): Collection
    {
        return Category::query()
            ->visibleTo($audience)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->visibleTo($audience)->orderBy('sort_order')->orderBy('name_ka')])
            ->orderBy('sort_order')
            ->orderBy('name_ka')
            ->get()
            ->map(fn (Category $cat) => [
                'label' => $cat->name_ka,
                'url' => route('category.show', $cat->slug),
                'target' => null,
                'children' => $cat->children->map(fn ($c) => [
                    'label' => $c->name_ka,
                    'url' => route('category.show', $c->slug),
                    'target' => null,
                    'children' => [],
                ])->values()->all(),
            ]);
    }
}
