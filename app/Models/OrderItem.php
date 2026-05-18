<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_sku_snapshot',
        'product_name_snapshot',
        'unit_price_retail',
        'unit_price_charged',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_retail' => 'decimal:2',
            'unit_price_charged' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
