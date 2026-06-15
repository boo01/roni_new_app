<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Session\SessionManager;

class Cart
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly SessionManager $session,
        private readonly Pricing $pricing,
    ) {}

    /**
     * Add a product (with an optional set of chosen options) to the cart.
     * The same product with the same options stacks onto one line; a
     * different option set becomes its own line.
     *
     * @param  array<int, array{attribute_id:int,attribute_name:string,attribute_slug:?string,value_id:int,value_name:string,value_slug:?string}>  $options
     */
    public function add(int $productId, int $quantity = 1, array $options = []): void
    {
        $items = $this->raw();
        $key = $this->lineKey($productId, $options);

        if (isset($items[$key])) {
            $items[$key]['quantity'] = max(1, $items[$key]['quantity'] + $quantity);
        } else {
            $items[$key] = [
                'product_id' => $productId,
                'quantity' => max(1, $quantity),
                'options' => array_values($options),
            ];
        }

        $this->save($items);
    }

    public function setQuantity(string $lineKey, int $quantity): void
    {
        $items = $this->raw();
        if (! isset($items[$lineKey])) {
            return;
        }
        if ($quantity <= 0) {
            unset($items[$lineKey]);
        } else {
            $items[$lineKey]['quantity'] = $quantity;
        }
        $this->save($items);
    }

    public function remove(string $lineKey): void
    {
        $items = $this->raw();
        unset($items[$lineKey]);
        $this->save($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /** @return array<string, array{product_id:int,quantity:int,options:array}> */
    public function raw(): array
    {
        return $this->normalize((array) $this->session->get(self::SESSION_KEY, []));
    }

    public function totalQuantity(): int
    {
        return (int) array_sum(array_column($this->raw(), 'quantity'));
    }

    public function isEmpty(): bool
    {
        return $this->totalQuantity() === 0;
    }

    /**
     * @return array{
     *   lines: array<int,array{line_key:string,product:Product,quantity:int,options:array,unit_retail:float,unit_charged:float,line_total:float,has_discount:bool}>,
     *   subtotal_retail: float,
     *   subtotal_charged: float,
     *   discount_total: float,
     *   total: float,
     * }
     */
    public function summary(?User $user): array
    {
        $raw = $this->raw();
        if ($raw === []) {
            return $this->emptySummary();
        }

        $products = Product::query()
            ->whereIn('id', array_column($raw, 'product_id'))
            ->where('is_active', true)
            ->with(['media', 'groupPrices'])
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotalRetail = 0.0;
        $subtotalCharged = 0.0;

        foreach ($raw as $key => $item) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                continue;
            }
            $price = $this->pricing->priceFor($user, $product);
            $quantity = (int) $item['quantity'];
            $lineTotal = round($price['charged'] * $quantity, 2);
            $lines[] = [
                'line_key' => (string) $key,
                'product' => $product,
                'quantity' => $quantity,
                'options' => $item['options'] ?? [],
                'unit_retail' => $price['retail'],
                'unit_charged' => $price['charged'],
                'line_total' => $lineTotal,
                'has_discount' => $price['has_discount'],
            ];
            $subtotalRetail += $price['retail'] * $quantity;
            $subtotalCharged += $lineTotal;
        }

        $subtotalRetail = round($subtotalRetail, 2);
        $subtotalCharged = round($subtotalCharged, 2);

        return [
            'lines' => $lines,
            'subtotal_retail' => $subtotalRetail,
            'subtotal_charged' => $subtotalCharged,
            'discount_total' => round($subtotalRetail - $subtotalCharged, 2),
            'total' => $subtotalCharged,
        ];
    }

    /**
     * Deterministic key for a product + its chosen option values, so the same
     * product with the same options stacks, while different options stay
     * on separate lines.
     */
    private function lineKey(int $productId, array $options): string
    {
        $valueIds = array_filter(array_map(fn ($o) => (int) ($o['value_id'] ?? 0), $options));
        sort($valueIds);

        return $productId . ':' . implode('-', $valueIds);
    }

    /**
     * Coerce stored data into the canonical shape, tolerating the legacy
     * [productId => quantity] format that older sessions may still hold.
     *
     * @param  array<mixed>  $items
     * @return array<string, array{product_id:int,quantity:int,options:array}>
     */
    private function normalize(array $items): array
    {
        $out = [];
        foreach ($items as $key => $value) {
            if (is_array($value) && isset($value['product_id'])) {
                $out[(string) $key] = [
                    'product_id' => (int) $value['product_id'],
                    'quantity' => max(1, (int) ($value['quantity'] ?? 1)),
                    'options' => array_values($value['options'] ?? []),
                ];

                continue;
            }

            // Legacy format: the array key was the product id, value the qty.
            $productId = (int) $key;
            if ($productId <= 0) {
                continue;
            }
            $out[$productId . ':'] = [
                'product_id' => $productId,
                'quantity' => max(1, (int) $value),
                'options' => [],
            ];
        }

        return $out;
    }

    private function save(array $items): void
    {
        $this->session->put(self::SESSION_KEY, $items);
    }

    private function emptySummary(): array
    {
        return [
            'lines' => [],
            'subtotal_retail' => 0.0,
            'subtotal_charged' => 0.0,
            'discount_total' => 0.0,
            'total' => 0.0,
        ];
    }
}
