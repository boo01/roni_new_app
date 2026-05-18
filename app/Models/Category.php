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
        'show_in_header',
        'header_sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'visible_to_retail' => 'boolean',
            'visible_to_b2b' => 'boolean',
            'show_in_header' => 'boolean',
            'sort_order' => 'integer',
            'header_sort_order' => 'integer',
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
}
