<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_PAID = 'paid';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_group_id',
        'status',
        'customer_snapshot',
        'subtotal_retail',
        'subtotal_charged',
        'discount_total',
        'total',
        'notes',
        'admin_notes',
        'contacted_at',
        'paid_at',
        'fulfilled_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_snapshot' => 'array',
            'subtotal_retail' => 'decimal:2',
            'subtotal_charged' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'contacted_at' => 'datetime',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
