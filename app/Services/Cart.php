<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Session\SessionManager;

class Cart
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly SessionManager $session,
        private readonly Pricing $pricing,
    ) {}

    public function add(int $productId, int $quantity = 1): void
    {
        $items = $this->raw();
        $items[$productId] = max(1, ($items[$productId] ?? 0) + $quantity);
        $this->save($items);
    }

    public function set(int $productId, int $quantity): void
    {
        $items = $this->raw();
        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }
        $this->save($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->raw();
        unset($items[$productId]);
        $this->save($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /** @return array<int,int> */
    public function raw(): array
    {
        return (array) $this->session->get(self::SESSION_KEY, []);
    }

    public function totalQuantity(): int
    {
        return (int) array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->totalQuantity() === 0;
    }

    /**
     * @return array{
     *   lines: array<int,array{product:Product,quantity:int,unit_retail:float,unit_charged:float,line_total:float,has_discount:bool}>,
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
            ->whereIn('id', array_keys($raw))
            ->where('is_active', true)
            ->with(['media', 'groupPrices'])
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotalRetail = 0.0;
        $subtotalCharged = 0.0;

        foreach ($raw as $productId => $quantity) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }
            $price = $this->pricing->priceFor($user, $product);
            $lineTotal = round($price['charged'] * $quantity, 2);
            $lines[] = [
                'product' => $product,
                'quantity' => (int) $quantity,
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
