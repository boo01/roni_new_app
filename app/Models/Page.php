<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title_ka',
        'body_ka',
        'contact_phone',
        'contact_email',
        'contact_locations',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'contact_locations' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
