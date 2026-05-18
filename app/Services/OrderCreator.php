<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderCreator
{
    public function __construct(private readonly Cart $cart) {}

    /**
     * @param  array{name:string,email:string,phone:?string,company_name:?string,company_tax_id:?string,address:?string,notes:?string}  $customer
     */
    public function create(?User $user, array $customer): Order
    {
        return DB::transaction(function () use ($user, $customer): Order {
            $summary = $this->cart->summary($user);

            if ($summary['lines'] === []) {
                throw new \RuntimeException('Cannot create an order from an empty cart.');
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_group_id' => $user?->customer_group_id,
                'status' => Order::STATUS_NEW,
                'customer_snapshot' => [
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'phone' => $customer['phone'] ?? null,
                    'company_name' => $customer['company_name'] ?? null,
                    'company_tax_id' => $customer['company_tax_id'] ?? null,
                    'address' => $customer['address'] ?? null,
                ],
                'subtotal_retail' => $summary['subtotal_retail'],
                'subtotal_charged' => $summary['subtotal_charged'],
                'discount_total' => $summary['discount_total'],
                'total' => $summary['total'],
                'notes' => $customer['notes'] ?? null,
            ]);

            foreach ($summary['lines'] as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'product_sku_snapshot' => $line['product']->sku,
                    'product_name_snapshot' => $line['product']->name_ka,
                    'unit_price_retail' => $line['unit_retail'],
                    'unit_price_charged' => $line['unit_charged'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->cart->clear();

            return $order->fresh(['items']);
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'R-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
