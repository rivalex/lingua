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
}
