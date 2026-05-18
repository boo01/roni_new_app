<?php

namespace App\Services\Roni5;

class CodeMatcher
{
    /** Caps-prefix style: NJ-012-128, SKU-AB-12, AB/123-X */
    private const ALPHA_PATTERN = '/\b[A-Z]{2,4}[-\/][A-Z0-9][A-Z0-9\-\/]*\b/';

    /**
     * Standalone digit run of 3-6 characters that is NOT immediately followed
     * by a size unit. Catches: "1505", "12345" — excludes "15g", "100ml".
     */
    private const NUMERIC_PATTERN = '/(?<![A-Za-z0-9])(\d{3,6})(?!\s*(?:g\b|kg\b|ml\b|cm\b|mm\b|გ\b|კგ\b|მგ\b|მლ\b|სმ\b|მმ\b))/u';

    /**
     * Extract a product code from a title. Returns null if no recognizable
     * code is present (caller should flag for manual review).
     */
    public function extract(string $title): ?string
    {
        if (preg_match(self::ALPHA_PATTERN, $title, $m)) {
            return $m[0];
        }
        if (preg_match(self::NUMERIC_PATTERN, $title, $m)) {
            return $m[1];
        }
        return null;
    }
}
