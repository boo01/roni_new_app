<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = ['name_ka', 'slug', 'sort_order', 'is_filterable', 'is_selectable', 'is_required'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_filterable' => 'boolean',
            'is_selectable' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('value_ka');
    }
}
