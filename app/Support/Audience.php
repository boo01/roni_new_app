<?php

namespace App\Support;

class Audience
{
    public const RETAIL = 'retail';

    public const B2B = 'b2b';

    /**
     * Resolve the current viewer's audience.
     * Guests and logged-in users without a customer group are retail.
     * Logged-in users with a customer group are B2B.
     */
    public static function current(): string
    {
        $user = auth()->user();
        return ($user && $user->customer_group_id !== null) ? self::B2B : self::RETAIL;
    }

    public static function column(): string
    {
        return 'visible_to_' . self::current();
    }
}
