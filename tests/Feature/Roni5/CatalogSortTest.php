<?php

namespace Tests\Feature\Roni5;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSortTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name_ka' => 'კატეგორია', 'slug' => 'kat', 'is_active' => true]);

        // name (ა < ბ < გ) deliberately not aligned with price, so the two
        // sort modes produce different orders.
        $this->make('P-A', 'ალფა', 30.00);
        $this->make('P-B', 'ბეტა', 10.00);
        $this->make('P-C', 'გამა', 20.00);
    }

    private function make(string $sku, string $name, float $price): void
    {
        $p = Product::create([
            'sku' => $sku, 'name_ka' => $name, 'slug' => $sku,
            'retail_price' => $price, 'is_active' => true, 'sort_order' => 0,
        ]);
        $p->categories()->attach($this->category->id, ['is_primary' => true]);
    }

    public function test_sort_by_price_ascending(): void
    {
        $this->get(route('category.show', ['slug' => 'kat', 'sort' => 'price_asc']))
            ->assertSeeInOrder(['ბეტა', 'გამა', 'ალფა']); // 10, 20, 30
    }

    public function test_sort_by_price_descending(): void
    {
        $this->get(route('category.show', ['slug' => 'kat', 'sort' => 'price_desc']))
            ->assertSeeInOrder(['ალფა', 'გამა', 'ბეტა']); // 30, 20, 10
    }

    public function test_sort_by_name_ascending(): void
    {
        $this->get(route('category.show', ['slug' => 'kat', 'sort' => 'name_asc']))
            ->assertSeeInOrder(['ალფა', 'ბეტა', 'გამა']);
    }

    public function test_sort_by_name_descending(): void
    {
        $this->get(route('category.show', ['slug' => 'kat', 'sort' => 'name_desc']))
            ->assertSeeInOrder(['გამა', 'ბეტა', 'ალფა']);
    }

    public function test_invalid_sort_falls_back_to_default(): void
    {
        $this->get(route('category.show', ['slug' => 'kat', 'sort' => 'evil; DROP TABLE']))
            ->assertOk();
    }
}
