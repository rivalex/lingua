<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Storage;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Contracts\WritesTranslationBatches;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\DottedKeyArray;
use Rivalex\Lingua\Support\PathGuard;
use Rivalex\Lingua\Support\PhpArrayExporter;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\Support\TranslationIdentityRules;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Support\TranslationValuePolicy;

/**
 * File-backed TranslationRepository implementation.
 *
 * Reads from and writes to lang/ files via AtomicFileWriter.
 * No queries to language_lines - DB is not used for runtime lookups.
 * All aggregation is done in PHP. File scans are O(files); cache is out of scope.
 */
final class FileRepository implements TranslationRepository, WritesTranslationBatches
{
    public function __construct(
        private readonly AtomicFileWriter $writer,
        private readonly TranslationFileReader $reader,
        private readonly string $langPath,
        private readonly BaseTranslationSource $bundled,
    ) {}

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
    ): TranslationLine {
        TranslationIdentityRules::assertValid($group, $key, $vendor, $locale);
        $this->assertNoCollision($group, $key, $isVendor, $vendor);
        $value = TranslationValuePolicy::normalize(LinguaType::text, $value, $group, $key);
        PathGuard::assertSafeSegment($locale, 'locale');
        if ($isVendor && $vendor !== null) {
            PathGuard::assertSafeSegment($vendor, 'vendor');
        }
        if ($group !== 'single') {
            PathGuard::assertSafeSegment($group, 'group');
        }

        $this->writeSingleKeyToFile($group, $key, $locale, $value, $isVendor, $vendor);

        return new TranslationLine(
            group: $group,
            key: $key,
            groupKey: $this->buildGroupKey($group, $key, $isVendor, $vendor),
            type: LinguaType::text,
            text: [$locale => $value],
            isVendor: $isVendor,
            vendor: $vendor,
            id: null,
        );
    }

    /**
     * Set/update the value of one key for one locale.
     */
    public function setValue(TranslationLine $line, string $locale, string $value): TranslationLine
    {
        TranslationIdentityRules::assertLocale($locale);
        $value = TranslationValuePolicy::normalize(LinguaType::text, $value, $line->group, $line->key);
        PathGuard::assertSafeSegment($locale, 'locale');
        $this->writeSingleKeyToFile($line->group, $line->key, $locale, $value, $line->isVendor, $line->vendor);

        $newText = array_merge($line->text, [$locale => $value]);

        return new TranslationLine(
            group: $line->group,
            key: $line->key,
            groupKey: $line->groupKey,
            type: $line->type,
            text: $newText,
            isVendor: $line->isVendor,
            vendor: $line->vendor,
            id: null,
        );
    }

    /**
     * Set several values, reading and writing each lang file once.
     *
     * Every change is validated before the first write, so a rejected change leaves all files unchanged.
     *
     * @param  list<array{line: TranslationLine, locale: string, value: string}>  $changes
     *
     * @throws \InvalidArgumentException on an invalid locale or unsafe path segment
     */
    public function setValues(array $changes): void
    {
        /** @var array<string, array{isJson: bool, values: array<string, string>}> $files */
        $files = [];

        foreach ($changes as $change) {
            $line = $change['line'];
            $locale = $change['locale'];

            TranslationIdentityRules::assertLocale($locale);
            $value = TranslationValuePolicy::normalize(LinguaType::text, $change['value'], $line->group, $line->key);
            $path = $this->resolveFilePath($line->group, $locale, $line->isVendor, $line->vendor);

            $files[$path] ??= ['isJson' => $line->group === 'single', 'values' => []];
            $files[$path]['values'][$line->key] = $value;
        }

        $this->withLangLock(function () use ($files): void {
            foreach ($files as $path => $file) {
                $this->writeKeysToFile($path, $file['isJson'], $file['values']);
            }
        });
    }

    /**
     * Change meta for a key.
     *
     * File-mode: type is not persisted. Rename (different group/key) is not supported
     * because cross-file atomicity cannot be guaranteed - throws RuntimeException.
     *
     * @throws \RuntimeException when group or key would change
     */
    public function updateMeta(TranslationLine $line, string $group, string $key, LinguaType $type): TranslationLine
    {
        if ($group !== $line->group || $key !== $line->key) {
            throw new \RuntimeException(
                '[Lingua] file-mode: rename group/key not supported; use delete+create.'
            );
        }

        return $line;
    }

    /**
     * Remove the value for one locale from its file.
     *
     * @throws VendorTranslationProtectedException if the line belongs to a vendor namespace
     */
    public function forgetLocale(TranslationLine $line, string $locale): void
    {
        if ($line->isVendor) {
            throw new VendorTranslationProtectedException;
        }

        TranslationIdentityRules::assertLocale($locale);
        PathGuard::assertSafeSegment($locale, 'locale');
        $this->deleteSingleKeyFromFile($line->group, $line->key, $locale, $line->isVendor, $line->vendor);
    }

    /**
     * Delete the key from ALL locale files.
     *
     * @throws VendorTranslationProtectedException if the line belongs to a vendor namespace
     */
    public function deleteKey(TranslationLine $line): void
    {
        if ($line->isVendor) {
            throw new VendorTranslationProtectedException;
        }

        $locales = $this->reader->discoverLocales($this->langPath);

        foreach ($locales as $locale) {
            try {
                PathGuard::assertSafeSegment($locale, 'locale');
                $this->deleteSingleKeyFromFile($line->group, $line->key, $locale, $line->isVendor, $line->vendor);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }
    }

    /**
     * Distinct group names, sorted.
     *
     * @return list<string>
     */
    public function groups(): array
    {
        $groups = [];
        $vendors = $this->reader->discoverVendorPackages($this->langPath);

        foreach ($this->reader->discoverLocales($this->langPath) as $locale) {
            $roots = [$this->langPath];
            foreach ($vendors as $vendor) {
                $roots[] = $this->langPath.'/vendor/'.$vendor;
            }

            foreach ($roots as $root) {
                $jsonFile = $root.'/'.$locale.'.json';
                if (file_exists($jsonFile)) {
                    $decoded = json_decode((string) file_get_contents($jsonFile), true);
                    if (is_array($decoded) && $decoded !== []) {
                        $groups[] = 'single';
                    }
                }

                $localeDir = $root.'/'.$locale;
                if (is_dir($localeDir)) {
                    foreach (glob($localeDir.'/*.php') ?: [] as $file) {
                        $groups[] = basename($file, '.php');
                    }
                }
            }
        }

        $groups = array_values(array_unique($groups));
        sort($groups);

        return $groups;
    }

    /**
     * Look up a single key.
     */
    public function find(string $group, string $key, bool $isVendor, ?string $vendor): ?TranslationLine
    {
        foreach ($this->buildUnifiedList() as $line) {
            if ($line->group === $group &&
                $line->key === $key &&
                $line->isVendor === $isVendor &&
                $line->vendor === $vendor) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Paginated list with PHP filtering.
     *
     * @return LengthAwarePaginatorContract<TranslationLine>
     */
    public function paginate(
        string $locale,
        string $search,
        string $group,
        bool $onlyMissing,
        int $perPage
    ): LengthAwarePaginatorContract {
        $defaultLocale = linguaDefaultLocale();
        $items = $this->buildUnifiedList();

        if ($group !== '') {
            $items = array_filter($items, fn ($l) => $l->group === $group);
        }

        if ($onlyMissing) {
            $items = array_filter($items, fn ($l) => ! isset($l->text[$locale]) || trim($l->text[$locale]) === '');
        }

        if ($search !== '') {
            $lower = strtolower($search);
            $items = array_filter($items, function (TranslationLine $l) use ($lower, $defaultLocale, $locale) {
                return str_contains(strtolower($l->groupKey), $lower)
                    || str_contains(strtolower($l->vendor ?? ''), $lower)
                    || str_contains(strtolower($l->value($defaultLocale)), $lower)
                    || str_contains(strtolower($l->value($locale)), $lower);
            });
        }

        $items = array_values($items);
        $total = count($items);
        $page = (int) (Paginator::resolveCurrentPage() ?: 1);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * All rows (for Statistics).
     *
     * @return Collection<int, TranslationLine>
     */
    public function all(bool $includeVendor = true): Collection
    {
        $items = $this->buildUnifiedList();

        if (! $includeVendor) {
            $items = array_filter($items, fn ($l) => ! $l->isVendor);
        }

        return collect(array_values($items));
    }

    /**
     * @return array{total: int, byLocale: array<string, int>}
     */
    public function counts(): array
    {
        $byLocale = [];
        $total = 0;

        foreach ($this->buildUnifiedList() as $line) {
            $total++;
            foreach ($line->text as $locale => $value) {
                if ($value !== null && trim((string) $value) !== '') {
                    $byLocale[$locale] = ($byLocale[$locale] ?? 0) + 1;
                }
            }
        }

        return ['total' => $total, 'byLocale' => $byLocale];
    }

    /**
     * @return array{total: int, translated: int, missing: int, percentage: float|int}
     */
    public function localeStats(string $locale): array
    {
        $counts = $this->counts();
        $total = $counts['total'];
        $translated = $counts['byLocale'][$locale] ?? 0;

        return [
            'total' => $total,
            'translated' => $translated,
            'missing' => $total - $translated,
            'percentage' => $total > 0 ? round(($translated / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Find by full key (`group.key`, `vendor::group.key`, `single.<sentence>`); a bare key
     * is accepted only when exactly one line has it.
     */
    public function findByKey(string $key): ?TranslationLine
    {
        $bare = [];

        foreach ($this->buildUnifiedList() as $line) {
            if ($line->groupKey === $key) {
                return $line;
            }

            if ($line->key === $key) {
                $bare[] = $line;
            }
        }

        return count($bare) === 1 ? $bare[0] : null;
    }

    /**
     * All rows for a group, optionally filtered by locale presence (PHP filter).
     *
     * @return Collection<int, TranslationLine>
     */
    public function byGroup(string $group, ?string $locale = null): Collection
    {
        $items = array_filter($this->buildUnifiedList(), function (TranslationLine $l) use ($group, $locale) {
            if ($l->group !== $group) {
                return false;
            }

            if ($locale !== null) {
                return isset($l->text[$locale]) && trim($l->text[$locale]) !== '';
            }

            return true;
        });

        return collect(array_values($items));
    }

    /**
     * All vendor rows for $vendor, optionally filtered by locale presence (PHP filter).
     *
     * @return Collection<int, TranslationLine>
     */
    public function vendor(string $vendor, ?string $locale = null): Collection
    {
        $items = array_filter($this->buildUnifiedList(), function (TranslationLine $l) use ($vendor, $locale) {
            if (! $l->isVendor || $l->vendor !== $vendor) {
                return false;
            }

            if ($locale !== null) {
                return isset($l->text[$locale]) && trim($l->text[$locale]) !== '';
            }

            return true;
        });

        return collect(array_values($items));
    }

    /**
     * Seed lang/ files for a newly installed locale (file-mode).
     *
     * Writes bundled translations merged with the default-locale key structure
     * (missing keys get empty-string values so the locale appears fully in the UI).
     * Vendor entries are skipped. Always ensures the lang/ directory exists and
     * always writes at least an empty {locale}.json so the locale is discoverable.
     *
     * @throws \InvalidArgumentException on unsafe locale path segment
     */
    public function installLocale(string $locale): void
    {
        PathGuard::assertSafeSegment($locale, 'locale');

        $this->withLangLock(function () use ($locale): void {
            $this->writer->ensureDir($this->langPath);

            $default = linguaDefaultLocale();

            // ── Step 1: collect bundled entries for this locale (non-vendor only)
            $bundledEntries = array_filter(
                $this->bundled->translationsFor($locale),
                fn (array $e) => ! $e['is_vendor']
            );

            // ── Step 2: mirror default-locale keys with empty values (skip for the default itself)
            $mirrored = [];
            if ($locale !== $default) {
                foreach ($this->buildUnifiedList() as $line) {
                    if ($line->isVendor) {
                        continue;
                    }
                    if (! isset($line->text[$default])) {
                        continue;
                    }
                    $indexKey = $line->group.'|'.$line->key;
                    $mirrored[$indexKey] = [
                        'group' => $line->group,
                        'key' => $line->key,
                        'value' => '',
                    ];
                }
            }

            // ── Step 3: merge - bundled values override empty mirrors
            $merged = $mirrored;
            foreach ($bundledEntries as $entry) {
                $indexKey = $entry['group'].'|'.$entry['key'];
                $merged[$indexKey] = [
                    'group' => $entry['group'],
                    'key' => $entry['key'],
                    'value' => $entry['value'],
                ];
            }

            // ── Step 4: group entries by file
            $singles = [];   // group === 'single' → {locale}.json flat array
            $groups = [];    // other groups → {locale}/{group}.php, key => value

            foreach ($merged as $entry) {
                if ($entry['group'] === 'single') {
                    $singles[$entry['key']] = $entry['value'];
                } else {
                    PathGuard::assertSafeSegment($entry['group'], 'group');
                    $groups[$entry['group']][$entry['key']] = $entry['value'];
                }
            }

            // ── Step 5: write, keeping what the locale's files already contain.
            // {locale}.json is always written (even {}) to guarantee discovery.
            $jsonPath = $this->langPath.'/'.$locale.'.json';
            $this->writer->putJson(
                $jsonPath,
                array_replace($singles, $this->readJsonFile($jsonPath)),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT
            );

            foreach ($groups as $group => $entries) {
                $localeDir = $this->langPath.'/'.$locale;
                $this->writer->ensureDir($localeDir);
                $phpPath = $localeDir.'/'.$group.'.php';
                $groupTranslations = $this->readPhpFile($phpPath);

                foreach ($entries as $key => $value) {
                    if (! DottedKeyArray::has($groupTranslations, (string) $key)) {
                        DottedKeyArray::set($groupTranslations, (string) $key, $value);
                    }
                }

                $this->writer->putPhp(
                    $phpPath,
                    "<?php\n\nreturn ".PhpArrayExporter::export($groupTranslations).";\n"
                );
            }
        });
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Build a unified list of TranslationLines from all locale files.
     * Cross-locale entries are merged: text[$locale] = value.
     *
     * @return list<TranslationLine>
     */
    private function buildUnifiedList(): array
    {
        $locales = $this->reader->discoverLocales($this->langPath);
        $indexed = [];

        foreach ($locales as $locale) {
            foreach ($this->reader->collect($this->langPath, $locale) as $entry) {
                $identKey = $entry['group'].'|'.$entry['key'].'|'.($entry['is_vendor'] ? '1' : '0').'|'.($entry['vendor'] ?? '');

                if (! isset($indexed[$identKey])) {
                    $indexed[$identKey] = [
                        'group' => $entry['group'],
                        'key' => (string) $entry['key'],
                        'is_vendor' => $entry['is_vendor'],
                        'vendor' => $entry['vendor'],
                        'text' => [],
                    ];
                }

                $indexed[$identKey]['text'][$locale] = $entry['value'];
            }
        }

        return array_values(array_map(
            fn ($item) => new TranslationLine(
                group: $item['group'],
                key: $item['key'],
                groupKey: $this->buildGroupKey($item['group'], $item['key'], $item['is_vendor'], $item['vendor']),
                type: LinguaType::text,
                text: $item['text'],
                isVendor: $item['is_vendor'],
                vendor: $item['vendor'],
                id: null,
            ),
            $indexed
        ));
    }

    /**
     * Write a single key to the appropriate file for one locale.
     * Reads current file, merges the key, writes atomically.
     */
    private function writeSingleKeyToFile(
        string $group,
        string $key,
        string $locale,
        string $value,
        bool $isVendor,
        ?string $vendor
    ): void {
        $filePath = $this->resolveFilePath($group, $locale, $isVendor, $vendor);

        $this->withLangLock(fn () => $this->writeKeysToFile($filePath, $group === 'single', [$key => $value]));
    }

    /**
     * Read a lang file once, merge the values into it and write it once.
     *
     * JSON keys are literal sentences (a dot is part of the key); PHP keys keep the shape the file has.
     *
     * @param  array<string, string>  $values
     */
    private function writeKeysToFile(string $filePath, bool $isJson, array $values): void
    {
        if ($isJson) {
            $existing = $this->readJsonFile($filePath);
            foreach ($values as $key => $value) {
                $existing[$key] = $value;
            }
            $this->writer->putJson($filePath, $existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);

            return;
        }

        $existing = $this->readPhpFile($filePath);
        foreach ($values as $key => $value) {
            DottedKeyArray::set($existing, (string) $key, $value);
        }
        $this->writer->putPhp($filePath, "<?php\n\nreturn ".PhpArrayExporter::export($existing).";\n");
    }

    /**
     * Remove a single key from the appropriate file for one locale.
     */
    private function deleteSingleKeyFromFile(
        string $group,
        string $key,
        string $locale,
        bool $isVendor,
        ?string $vendor
    ): void {
        $filePath = $this->resolveFilePath($group, $locale, $isVendor, $vendor);

        $this->withLangLock(function () use ($group, $key, $filePath): void {
            if (! file_exists($filePath)) {
                return;
            }

            if ($group === 'single') {
                $decoded = json_decode(file_get_contents($filePath), true);
                if (! is_array($decoded)) {
                    return;
                }
                unset($decoded[$key]);
                $this->writer->putJson($filePath, $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
            } else {
                $loaded = include $filePath;
                if (! is_array($loaded)) {
                    return;
                }
                DottedKeyArray::forget($loaded, $key);
                $this->writer->putPhp($filePath, "<?php\n\nreturn ".PhpArrayExporter::export($loaded).";\n");
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($filePath), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function readPhpFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $loaded = include $filePath;

        return is_array($loaded) ? $loaded : [];
    }

    /**
     * Run $fn while holding the exclusive lang-file lock. Re-entrant: a nested call runs $fn without locking again.
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return T
     *
     * @throws \RuntimeException when the lock cannot be taken
     */
    private function withLangLock(callable $fn): mixed
    {
        static $depth = 0;

        if ($depth > 0) {
            $depth++;

            try {
                return $fn();
            } finally {
                $depth--;
            }
        }

        $lockPath = storage_path('framework/lingua-lang.lock');
        $this->writer->ensureDir(dirname($lockPath));

        $handle = @fopen($lockPath, 'c');

        if ($handle === false || ! flock($handle, LOCK_EX)) {
            if ($handle !== false) {
                fclose($handle);
            }

            throw new \RuntimeException('[Lingua] Cannot lock the lang files for writing.');
        }

        $depth++;

        try {
            return $fn();
        } finally {
            $depth--;
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Reject a key that collides with an existing key of the group, and a new group that differs
     * from an existing one only by case.
     *
     * @throws \InvalidArgumentException on a collision
     */
    private function assertNoCollision(string $group, string $key, bool $isVendor, ?string $vendor): void
    {
        $error = TranslationIdentityRules::collisionError($group, $key, TranslationIdentityRules::existingKeys($this, $group, $isVendor, $vendor));

        if ($error === null) {
            $groups = $this->groups();
            $error = in_array($group, $groups, true) ? null : TranslationIdentityRules::groupCaseError($group, $groups);
        }

        if ($error !== null) {
            throw new \InvalidArgumentException('[Lingua] '.$error);
        }
    }

    /**
     * Resolve the filesystem path for a group/locale/vendor combination.
     * All segments are validated by PathGuard before use.
     */
    private function resolveFilePath(string $group, string $locale, bool $isVendor, ?string $vendor): string
    {
        PathGuard::assertSafeSegment($locale, 'locale');

        if ($isVendor && $vendor !== null) {
            PathGuard::assertSafeSegment($vendor, 'vendor');
            $vendorRoot = $this->langPath.'/vendor/'.$vendor;

            if ($group === 'single') {
                return $vendorRoot.'/'.$locale.'.json';
            }

            PathGuard::assertSafeSegment($group, 'group');

            return $vendorRoot.'/'.$locale.'/'.$group.'.php';
        }

        if ($group === 'single') {
            return $this->langPath.'/'.$locale.'.json';
        }

        PathGuard::assertSafeSegment($group, 'group');

        return $this->langPath.'/'.$locale.'/'.$group.'.php';
    }

    /**
     * Build a group_key string matching Translation::buildGroupKey() logic.
     */
    private function buildGroupKey(string $group, string $key, bool $isVendor, ?string $vendor): string
    {
        $prefix = $isVendor && $vendor ? $vendor.'::' : '';

        return $prefix.$group.'.'.$key;
    }
}
