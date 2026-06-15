<?php

namespace Tests\Feature\Roni5;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Services\Cart;
use App\Services\OrderCreator;
use App\Services\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:Product,1:Attribute,2:AttributeValue,3:AttributeValue} */
    private function productWithColor(bool $required = true): array
    {
        $category = Category::create(['name_ka' => 'კატეგორია', 'slug' => 'cat', 'is_active' => true]);

        $product = Product::create([
            'sku' => 'OPT-1', 'name_ka' => 'პროდუქტი', 'slug' => 'opt-1',
            'retail_price' => 10.00, 'is_active' => true,
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true]);

        $color = Attribute::create([
            'name_ka' => 'ფერი', 'slug' => 'color',
            'is_selectable' => true, 'is_required' => $required,
        ]);
        $red = AttributeValue::create(['attribute_id' => $color->id, 'value_ka' => 'წითელი', 'slug' => 'red']);
        $blue = AttributeValue::create(['attribute_id' => $color->id, 'value_ka' => 'ლურჯი', 'slug' => 'blue']);
        $product->attributeValues()->attach([$red->id, $blue->id]);

        return [$product->fresh(['attributeValues.attribute']), $color, $red, $blue];
    }

    private function freshCart(): Cart
    {
        $session = $this->app['session'];
        $session->start();

        return new Cart($session, new Pricing());
    }

    public function test_same_options_stack_while_different_options_split(): void
    {
        [$product, $color, $red, $blue] = $this->productWithColor();
        $cart = $this->freshCart();

        $opt = fn (AttributeValue $v) => [[
            'attribute_id' => $color->id, 'attribute_name' => 'ფერი', 'attribute_slug' => 'color',
            'value_id' => $v->id, 'value_name' => $v->value_ka, 'value_slug' => $v->slug,
        ]];

        $cart->add($product->id, 1, $opt($red));
        $cart->add($product->id, 1, $opt($red));   // stacks onto the red line
        $cart->add($product->id, 1, $opt($blue));  // separate line

        $summary = $cart->summary(null);
        $this->assertCount(2, $summary['lines']);

        $byColor = collect($summary['lines'])
            ->mapWithKeys(fn ($l) => [$l['options'][0]['value_name'] => $l['quantity']]);
        $this->assertSame(2, $byColor['წითელი']);
        $this->assertSame(1, $byColor['ლურჯი']);
    }

    public function test_order_snapshots_chosen_options(): void
    {
        [$product, $color, $red] = $this->productWithColor();
        $cart = $this->freshCart();

        $cart->add($product->id, 2, [[
            'attribute_id' => $color->id, 'attribute_name' => 'ფერი', 'attribute_slug' => 'color',
            'value_id' => $red->id, 'value_name' => 'წითელი', 'value_slug' => 'red',
        ]]);

        $order = (new OrderCreator($cart))->create(null, [
            'name' => 'ტესტ', 'email' => 't@example.com', 'phone' => '500', 'address' => 'მის.',
        ]);

        $snapshot = $order->items->first()->options_snapshot;
        $this->assertSame('ფერი', $snapshot[0]['attribute_name']);
        $this->assertSame('წითელი', $snapshot[0]['value_name']);
    }

    public function test_required_option_blocks_add_to_cart(): void
    {
        [$product, $color] = $this->productWithColor(required: true);

        $this->post(route('cart.add', $product), [])
            ->assertSessionHasErrors('options.' . $color->id)
            ->assertSessionMissing('cart');
    }

    public function test_valid_choice_is_recorded_on_the_cart(): void
    {
        [$product, $color, $red] = $this->productWithColor(required: true);

        $this->post(route('cart.add', $product), ['options' => [$color->id => $red->id]])
            ->assertRedirect()
            ->assertSessionHas('cart', function (array $cart) {
                $line = array_values($cart)[0];
                return $line['options'][0]['value_name'] === 'წითელი';
            });
    }

    public function test_optional_option_may_be_omitted(): void
    {
        [$product] = $this->productWithColor(required: false);

        $this->post(route('cart.add', $product), [])->assertRedirect();
        $this->post(route('cart.add', $product), [])->assertSessionHas('cart');
    }
}
