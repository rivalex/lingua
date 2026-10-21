<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use Rivalex\Lingua\Locales\LocaleRegistry;
use Rivalex\Lingua\Transfer\Enums\TransferScope;

/**
 * Single source of truth for CSV/XLSX/ODS column layout and header labels.
 *
 * All header-building logic lives here; no other class should concatenate
 * column names or know the meta-prefix rule.
 */
final class TransferSchema
{
    public const KEY = '_key';

    public const TYPE = '_type';

    public const VENDOR = '_vendor';

    /**
     * Return the source-locale header label (e.g. "en - English (source)").
     */
    public static function sourceHeader(string $defaultLocale): string
    {
        $name = app(LocaleRegistry::class)->info($defaultLocale)?->name ?? $defaultLocale;

        return "{$defaultLocale} - {$name} (source)";
    }

    /**
     * Return the target-locale header label (e.g. "it - Italian").
     */
    public static function targetHeader(string $locale): string
    {
        $name = app(LocaleRegistry::class)->info($locale)?->name ?? $locale;

        return "{$locale} - {$name}";
    }

    /**
     * Returns true when the header is a meta column (starts with '_').
     */
    public static function isMeta(string $header): bool
    {
        return str_starts_with($header, '_');
    }

    /**
     * Build the ordered header array for a given export scope.
     *
     * @param  array<int, string>  $allLocaleCodes  All installed locale codes (for multiLocale scope).
     */
    public static function buildHeaders(
        string $defaultLocale,
        ?string $targetLocale,
        array $allLocaleCodes,
        TransferScope $scope,
    ): array {
        return match ($scope) {
            TransferScope::bilingual => [
                self::KEY,
                self::TYPE,
                self::sourceHeader($defaultLocale),
                self::targetHeader($targetLocale ?? $defaultLocale),
                self::VENDOR,
            ],
            TransferScope::multiLocale => array_merge(
                [self::KEY, self::TYPE],
                array_map(fn (string $code) => self::targetHeader($code), $allLocaleCodes),
                [self::VENDOR],
            ),
            TransferScope::jsonNative => [],
        };
    }
}
