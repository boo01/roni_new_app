<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'parent_id',
        'location',
        'type',
        'label',
        'page_id',
        'category_id',
        'url',
        'target_blank',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'target_blank' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Display label: the explicit override, else the referenced item's name.
     */
    public function resolveLabel(): ?string
    {
        if (filled($this->label)) {
            return $this->label;
        }

        return match ($this->type) {
            'page' => $this->page?->title_ka,
            'category' => $this->category?->name_ka,
            default => $this->url,
        };
    }

    /**
     * Destination URL, or null if the target no longer exists (item is skipped).
     */
    public function resolveUrl(): ?string
    {
        return match ($this->type) {
            'page' => $this->page ? match ($this->page->slug) {
                'about' => route('page.about'),
                'contact' => route('page.contact'),
                default => route('page.show', $this->page->slug),
            } : null,
            'category' => $this->category ? route('category.show', $this->category->slug) : null,
            'link' => filled($this->url) ? $this->url : null,
            default => null,
        };
    }
}
