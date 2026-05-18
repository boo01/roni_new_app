<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'discount_percent',
        'is_default_for_b2b',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'is_default_for_b2b' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductGroupPrice::class);
    }
}
