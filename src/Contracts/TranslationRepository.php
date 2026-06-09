<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;

/**
 * Driver-neutral translation storage contract.
 *
 * Implementations: DatabaseRepository (database-mode) and FileRepository (file-mode).
 * All aggregations must use PHP — never SQL JSON functions.
 */
interface TranslationRepository
{
    // ── Write ────────────────────────────────────────────────────────────────

    /**
     * Create a new key with a value for the default locale.
     *
     * @throws \InvalidArgumentException on unsafe path segment
     */
    public function create(
        string $group,
        string $key,
        LinguaType $type,
        string $locale,
        string $value,
        bool $isVendor = false,
        ?string $vendor = null
    ): TranslationLine;

    /**
     * Set/update the value of one key for one locale.
     *
     * @throws \InvalidArgumentException on unsafe path segment (file-mode)
     */
    public function setValue(TranslationLine $line, string $locale, string $value): TranslationLine;

    /**
     * Change meta (group/key/type) for a key.
     *
     * File-mode: type is not persisted (flat files). If group or key differ from
     * the current line a RuntimeException is thrown — use delete+create instead.
     *
     * @throws \RuntimeException in file-mode when group or key would change
     */
    public function updateMeta(TranslationLine $line, string $group, string $key, LinguaType $type): TranslationLine;

    /**
     * Remove the value for one locale (does not delete the key). No-op on vendor lines.
     */
    public function forgetLocale(TranslationLine $line, string $locale): void;

    /**
     * Delete the entire key (all locales). Callers must guard against vendor lines.
     */
    public function deleteKey(TranslationLine $line): void;

    // ── Read (UI) ────────────────────────────────────────────────────────────

    /**
     * Distinct group names, sorted.
     *
     * @return list<string>
     */
    public function groups(): array;

    /**
     * Look up a single key.
     */
    public function find(string $group, string $key, bool $isVendor, ?string $vendor): ?TranslationLine;

    /**
     * Paginated list for the Translations table component.
     *
     * @return LengthAwarePaginator<TranslationLine>
     */
    public function paginate(
        string $locale,
        string $search,
        string $group,
        bool $onlyMissing,
        int $perPage
    ): LengthAwarePaginator;

    /**
     * All rows (for Statistics).
     *
     * @return Collection<int, TranslationLine>
     */
    public function all(bool $includeVendor = true): Collection;

    // ── Stats (PHP aggregation only) ─────────────────────────────────────────

    /**
     * @return array{total: int, byLocale: array<string, int>}
     */
    public function counts(): array;

    /**
     * @return array{total: int, translated: int, missing: int, percentage: float|int}
     */
    public function localeStats(string $locale): array;

    // ── Facade lookups ───────────────────────────────────────────────────────

    /**
     * Find by full dotted key (group resolved from first segment in file-mode).
     */
    public function findByKey(string $key): ?TranslationLine;

    /**
     * All rows for a group, optionally filtered to those with a value for $locale.
     *
     * database-mode: uses whereNotNull('text->'.$locale) (SQL JSON, DB-only).
     * file-mode: PHP filter — isset($text[$locale]) && trim($text[$locale]) !== ''.
     *
     * @return Collection<int, TranslationLine>
     */
    public function byGroup(string $group, ?string $locale = null): Collection;

    /**
     * All vendor rows for $vendor, optionally filtered by locale presence.
     *
     * database-mode: SQL JSON filter. file-mode: PHP filter.
     *
     * @return Collection<int, TranslationLine>
     */
    public function vendor(string $vendor, ?string $locale = null): Collection;
}
