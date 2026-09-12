<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        'meta_title',
        'meta_description',
        'meta_keywords',
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

    /**
     * The product's categories that this audience may actually open. The raw
     * relation can include categories hidden from them, whose pages 404.
     *
     * @return Collection<int, Category>
     */
    public function visibleCategories(string $audience): Collection
    {
        return $this->categories
            ->filter(fn (Category $c) => $c->is_active && $c->{'visible_to_' . $audience})
            ->values();
    }

    /**
     * The most specific category this product sits in that the given audience
     * may actually see. Most products are attached to both a root and one of
     * its subcategories, and the primary flag usually points at the root — so
     * depth decides, and the primary flag only breaks ties. This is what the
     * breadcrumb trail and the prev/next arrows are built from.
     */
    public function breadcrumbCategory(string $audience): ?Category
    {
        return $this->visibleCategories($audience)
            ->sortByDesc(fn (Category $c) => [
                $c->ancestors()->count(),
                $c->pivot->is_primary ? 1 : 0,
            ])
            ->first();
    }

    /**
     * Root-to-leaf category chain for the breadcrumb, every entry linkable.
     * Ancestors hidden from this audience are dropped rather than linked —
     * their category pages would 404 for this visitor.
     *
     * @return Collection<int, Category>
     */
    public function breadcrumbTrail(string $audience): Collection
    {
        $category = $this->breadcrumbCategory($audience);

        if (! $category) {
            return collect();
        }

        return $category->ancestors()
            ->push($category)
            ->filter(fn (Category $c) => $c->is_active && $c->{'visible_to_' . $audience})
            ->values();
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
     * Customer-selectable option groups for this product: the product's
     * attribute values grouped by their attribute, limited to attributes
     * flagged as selectable. Each entry is
     * ['attribute' => Attribute, 'values' => Collection<AttributeValue>].
     *
     * @return Collection<int, array{attribute: Attribute, values: Collection}>
     */
    public function selectableOptionGroups(): Collection
    {
        $this->loadMissing('attributeValues.attribute');

        return $this->attributeValues
            ->filter(fn (AttributeValue $v) => $v->attribute?->is_selectable)
            ->groupBy(fn (AttributeValue $v) => $v->attribute->id)
            ->map(fn (Collection $values) => [
                'attribute' => $values->first()->attribute,
                'values' => $values->sortBy([['sort_order', 'asc'], ['value_ka', 'asc']])->values(),
            ])
            ->sortBy(fn (array $g) => $g['attribute']->sort_order)
            ->values();
    }

    public function hasSelectableOptions(): bool
    {
        return $this->selectableOptionGroups()->isNotEmpty();
    }

    public function hasRequiredOptions(): bool
    {
        return $this->selectableOptionGroups()->contains(fn (array $g) => $g['attribute']->is_required);
    }

    /**
     * @return array{retail: float, charged: float, has_discount: bool}
     */
    public function priceFor(?User $user): array
    {
        return app(\App\Services\Pricing::class)->priceFor($user, $this);
    }

    /**
     * SEO / social-share metadata. Each field uses the manual override if set,
     * otherwise it's auto-generated from the product.
     *
     * @return array{title:string, description:string, keywords:?string, og_image:?string, og_type:string}
     */
    public function seoMeta(): array
    {
        $description = $this->meta_description
            ?: (string) Str::of(strip_tags((string) $this->description_ka))->squish()->limit(160);

        if (blank($description)) {
            $description = trim($this->name_ka . ' · ' . $this->sku, ' ·');
        }

        return [
            'title' => $this->meta_title ?: $this->name_ka,
            'description' => $description,
            'keywords' => $this->meta_keywords ?: $this->autoKeywords(),
            'og_image' => $this->getFirstMediaUrl('images', 'card') ?: null,
            'og_type' => 'product',
        ];
    }

    private function autoKeywords(): ?string
    {
        $words = $this->categories
            ->pluck('name_ka')
            ->prepend($this->name_ka)
            ->filter()
            ->unique()
            ->take(10);

        return $words->isNotEmpty() ? $words->implode(', ') : null;
    }
}
