<?php

namespace Tests\Feature\Roni5;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductNeighbourNavTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name_ka' => 'კატეგორია', 'slug' => 'kat', 'is_active' => true]);
    }

    private function make(string $sku, string $name, array $attrs = []): Product
    {
        $p = Product::create(array_merge([
            'sku' => $sku, 'name_ka' => $name, 'slug' => $sku,
            'retail_price' => 10.00, 'is_active' => true, 'sort_order' => 0,
        ], $attrs));
        $p->categories()->attach($this->category->id, ['is_primary' => true]);

        return $p;
    }

    public function test_middle_product_links_to_both_neighbours(): void
    {
        $this->make('P-A', 'ალფა');
        $this->make('P-B', 'ბეტა');
        $this->make('P-C', 'გამა');

        $this->get(route('product.show', 'P-B'))
            ->assertOk()
            ->assertSee(route('product.show', 'P-A'))
            ->assertSee(route('product.show', 'P-C'));
    }

    public function test_first_product_has_no_previous_and_last_has_no_next(): void
    {
        $this->make('P-A', 'ალფა');
        $this->make('P-B', 'ბეტა');

        $this->get(route('product.show', 'P-A'))
            ->assertOk()
            ->assertSee(route('product.show', 'P-B'))
            ->assertDontSee('rel="prev"', false);

        $this->get(route('product.show', 'P-B'))
            ->assertOk()
            ->assertSee(route('product.show', 'P-A'))
            ->assertDontSee('rel="next"', false);
    }

    public function test_neighbours_skip_products_hidden_from_this_audience(): void
    {
        $this->make('P-A', 'ალფა');
        $this->make('P-B', 'ბეტა', ['visible_to_retail' => false]); // B2B-only
        $this->make('P-C', 'გამა');

        // A retail visitor must step straight over the hidden middle product.
        $this->get(route('product.show', 'P-A'))
            ->assertOk()
            ->assertSee(route('product.show', 'P-C'))
            ->assertDontSee(route('product.show', 'P-B'));
    }

    public function test_a_lone_product_shows_no_navigation_at_all(): void
    {
        $this->make('P-A', 'ალფა');

        $this->get(route('product.show', 'P-A'))
            ->assertOk()
            ->assertDontSee('rel="prev"', false)
            ->assertDontSee('rel="next"', false);
    }
}
