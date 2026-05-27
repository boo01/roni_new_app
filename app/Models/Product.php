<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'sku',
        'name_ka',
        'slug',
        'description_ka',
        'retail_price',
        'stock_quantity',
        'track_stock',
        'is_active',
        'sort_order',
        'visible_to_retail',
        'visible_to_b2b',
    ];

    protected function casts(): array
    {
        return [
            'retail_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'visible_to_retail' => 'boolean',
            'visible_to_b2b' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope: product is visible AND at least one attached category is
     * visible to the same audience. If a product's only category is
     * hidden, the product is unreachable for that audience.
     */
    public function scopeVisibleTo($query, string $audience)
    {
        $col = 'visible_to_' . $audience;
        return $query
            ->where('is_active', true)
            ->where($col, true)
            ->whereHas('categories', fn ($c) => $c->where('is_active', true)->where($col, true));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('card')
            ->width(600)
            ->height(600)
            ->nonQueued();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'sort_order'])
            ->orderByPivot('is_primary', 'desc')
            ->orderByPivot('sort_order');
    }

    public function primaryCategory(): ?Category
    {
        return $this->categories->firstWhere('pivot.is_primary', true) ?? $this->categories->first();
    }

    public function groupPrices(): HasMany
    {
        return $this->hasMany(ProductGroupPrice::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    /**
     * @return array{retail: float, charged: float, has_discount: bool}
     */
    public function priceFor(?User $user): array
    {
        return app(\App\Services\Pricing::class)->priceFor($user, $this);
    }
}
