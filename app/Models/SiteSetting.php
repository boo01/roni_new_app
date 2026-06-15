<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'contact_phone',
        'contact_email',
        'locations',
    ];

    protected function casts(): array
    {
        return [
            'locations' => 'array',
        ];
    }

    /**
     * The single settings row, created on first access.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
