<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name_ka',
        'slug',
        'description_ka',
        'sort_order',
        'is_active',
        'visible_to_retail',
        'visible_to_b2b',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'visible_to_retail' => 'boolean',
            'visible_to_b2b' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeVisibleTo($query, string $audience)
    {
        return $query
            ->where('is_active', true)
            ->where('visible_to_' . $audience, true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['is_primary', 'sort_order']);
    }

    /**
     * IDs of this category plus every descendant, so a parent page can list
     * products that live in its subcategories. Cheap: the tree is small and
     * loaded once per request.
     */
    public function descendantAndSelfIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantAndSelfIds());
        }
        return $ids;
    }

    /** Ancestor chain from the top-level root down to (but excluding) this node. */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $chain = collect();
        $node = $this->parent;
        $guard = 0;
        while ($node && $guard++ < 20) {
            $chain->prepend($node);
            $node = $node->parent;
        }
        return $chain;
    }
}
