<?php

namespace App\Support;

use Illuminate\Support\Str;
use Transliterator;

class Slug
{
    public static function generate(string $text, ?string $fallbackHint = null): string
    {
        $slug = Str::slug($text);
        if ($slug !== '') {
            return $slug;
        }

        if (class_exists(Transliterator::class)) {
            $tr = Transliterator::create('Any-Latin; Latin-ASCII; Lower');
            if ($tr !== null) {
                $latin = $tr->transliterate($text) ?: '';
                $slug = Str::slug($latin);
                if ($slug !== '') {
                    return $slug;
                }
            }
        }

        if ($fallbackHint) {
            $slug = Str::slug($fallbackHint);
            if ($slug !== '') {
                return $slug;
            }
        }

        return 'item-' . substr(md5($text), 0, 8);
    }
}
