<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'logo',
        'meta_title',
        'meta_description',
        'contact_phone',
        'contact_email',
        'whatsapp',
        'social_links',
        'locations',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'locations' => 'array',
        ];
    }

    private static ?self $cached = null;

    /**
     * The single settings row, created on first access and cached for the
     * rest of the request (nav, layout head and footer all read it).
     */
    public static function current(): self
    {
        return static::$cached ??= static::firstOrCreate(['id' => 1]);
    }

    /** Digits-only WhatsApp number for wa.me links. */
    public function whatsappNumber(): ?string
    {
        return $this->whatsapp ? preg_replace('/\D/', '', $this->whatsapp) : null;
    }
}
