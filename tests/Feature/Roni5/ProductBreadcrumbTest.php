<?php

namespace Tests\Feature\Roni5;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBreadcrumbTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, string $slug, ?Category $parent = null, array $attrs = []): Category
    {
        return Category::create(array_merge([
            'name_ka' => $name,
            'slug' => $slug,
            'is_active' => true,
            'parent_id' => $parent?->id,
        ], $attrs));
    }

    private function product(string $sku, array $categories): Product
    {
        $p = Product::create([
            'sku' => $sku, 'name_ka' => $sku, 'slug' => $sku,
            'retail_price' => 10.00, 'is_active' => true, 'sort_order' => 0,
        ]);
        $p->categories()->attach($categories);

        return $p;
    }

    public function test_breadcrumb_shows_every_ancestor_as_a_link(): void
    {
        $root = $this->category('ფესვი', 'root');
        $sub = $this->category('ქვე', 'sub', $root);
        $subSub = $this->category('ქვექვე', 'subsub', $sub);

        // Attached at every level, the way the real catalog does it.
        $this->product('P-1', [$root->id => ['is_primary' => true], $sub->id => ['is_primary' => false], $subSub->id => ['is_primary' => false]]);

        $this->get(route('product.show', 'P-1'))
            ->assertOk()
            ->assertSeeInOrder([
                route('category.show', 'root'),
                route('category.show', 'sub'),
                route('category.show', 'subsub'),
            ]);
    }

    public function test_deepest_category_wins_over_the_primary_flag(): void
    {
        // The catalog marks the ROOT as primary, so the breadcrumb must not
        // stop there — the subcategory is the more specific place.
        $root = $this->category('ფესვი', 'root');
        $sub = $this->category('ქვე', 'sub', $root);

        $this->product('P-1', [$root->id => ['is_primary' => true], $sub->id => ['is_primary' => false]]);

        $this->get(route('product.show', 'P-1'))
            ->assertOk()
            ->assertSee(route('category.show', 'sub'));
    }

    public function test_an_ancestor_hidden_from_this_audience_is_not_linked(): void
    {
        // A retail visitor must never be handed a link to a B2B-only category:
        // that page 404s for them.
        $root = $this->category('ფესვი', 'root', null, ['visible_to_retail' => false]);
        $sub = $this->category('ქვე', 'sub', $root);

        $this->product('P-1', [$root->id => ['is_primary' => false], $sub->id => ['is_primary' => true]]);

        $this->get(route('product.show', 'P-1'))
            ->assertOk()
            ->assertSee(route('category.show', 'sub'))
            ->assertDontSee(route('category.show', 'root'));
    }

    public function test_category_page_breadcrumb_links_the_whole_chain(): void
    {
        $root = $this->category('ფესვი', 'root');
        $sub = $this->category('ქვე', 'sub', $root);
        $subSub = $this->category('ქვექვე', 'subsub', $sub);
        $this->product('P-1', [$subSub->id => ['is_primary' => true]]);

        $this->get(route('category.show', 'subsub'))
            ->assertOk()
            ->assertSeeInOrder([
                route('category.show', 'root'),
                route('category.show', 'sub'),
            ]);
    }
}
