<?php

declare(strict_types=1);

namespace Rivalex\Lingua\TranslationManager;

final class CacheKey
{
    public static function forGroup(string $locale, string $group): string
    {
        $prefix = config('lingua.cache.prefix', 'lingua.trans');

        return "{$prefix}.{$locale}.{$group}";
    }

    /**
     * Cache key for a vendor namespace group.
     *
     * Uses `{vendor}::` as a separator that cannot appear in plain group names,
     * guaranteeing no collision with forGroup() keys.
     */
    public static function forVendorGroup(string $locale, string $vendor, string $group): string
    {
        $prefix = config('lingua.cache.prefix', 'lingua.trans');

        return "{$prefix}.{$locale}.{$vendor}::{$group}";
    }
}
