<?php

namespace Tests\Feature;

use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductGroupPrice;
use App\Models\User;
use App\Services\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    private Pricing $pricing;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = new Pricing();
        $this->product = Product::create([
            'sku' => 'TEST-001',
            'name_ka' => 'სატესტო პროდუქტი',
            'slug' => 'test-001',
            'retail_price' => 100.00,
        ]);
    }

    public function test_guest_sees_retail_price(): void
    {
        $result = $this->pricing->priceFor(null, $this->product);

        $this->assertSame(100.0, $result['retail']);
        $this->assertSame(100.0, $result['charged']);
        $this->assertFalse($result['has_discount']);
    }

    public function test_b2c_user_without_group_sees_retail_price(): void
    {
        $user = User::create([
            'name' => 'B2C Customer',
            'email' => 'b2c@example.com',
            'password' => 'secret',
        ]);

        $result = $this->pricing->priceFor($user, $this->product);

        $this->assertSame(100.0, $result['charged']);
        $this->assertFalse($result['has_discount']);
    }

    public function test_b2b_user_gets_group_discount_percent(): void
    {
        $group = CustomerGroup::create([
            'name' => 'VIP',
            'slug' => 'vip',
            'discount_percent' => 15,
        ]);
        $user = User::create([
            'name' => 'Acme Ltd',
            'email' => 'b2b@example.com',
            'password' => 'secret',
            'customer_group_id' => $group->id,
        ]);

        $result = $this->pricing->priceFor($user, $this->product);

        $this->assertSame(100.0, $result['retail']);
        $this->assertSame(85.0, $result['charged']);
        $this->assertTrue($result['has_discount']);
    }

    public function test_product_group_price_override_beats_group_percent(): void
    {
        $group = CustomerGroup::create([
            'name' => 'Wholesale',
            'slug' => 'wholesale',
            'discount_percent' => 10,
        ]);
        $user = User::create([
            'name' => 'Big Co',
            'email' => 'wholesale@example.com',
            'password' => 'secret',
            'customer_group_id' => $group->id,
        ]);
        ProductGroupPrice::create([
            'product_id' => $this->product->id,
            'customer_group_id' => $group->id,
            'price' => 70.00,
        ]);

        $result = $this->pricing->priceFor($user, $this->product);

        $this->assertSame(70.0, $result['charged']);
        $this->assertTrue($result['has_discount']);
    }
}
