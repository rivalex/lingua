<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\Factories\TranslationFactory;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Locales\LocaleRegistry;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\CacheCircuit;
use Rivalex\Lingua\Support\DottedKeyArray;
use Rivalex\Lingua\Support\PathGuard;
use Rivalex\Lingua\Support\PhpArrayExporter;
use Rivalex\Lingua\Support\RequestMemo;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\TranslationManager\CacheKey;
use Throwable;

/**
 * Class Translation
 *
 * Translation model for managing language translations in the application.
 *
 * @property string $id UUID identifier for the translation
 * @property string $group Translation group name (e.g., 'single', 'validation', etc.)
 * @property string $key Translation key within the group
 * @property string $group_key Translation group and key concatenated.
 * @property LinguaType $type Type of translation (text, html, etc.)
 * @property array $text Associative array of translations (locale => translation)
 * @property bool $is_vendor Indicates if the translation is a vendor translation
 * @property string $vendor Vendor name if the translation is a vendor translation
 * @property Carbon $created_at Creation timestamp
 * @property Carbon $updated_at Last update timestamp
 * @property-read string $lang_key Get the full group key (e.g., 'single.welcome')
 */
#[UseFactory(TranslationFactory::class)]
class Translation extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'language_lines';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'group' => 'string',
        'key' => 'string',
        'type' => LinguaType::class,
        'group_key' => 'string',
        'text' => 'array',
        'is_vendor' => 'boolean',
        'vendor' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group',
        'key',
        // Derived (group.key composite), NOT NULL + UNIQUE by design. Fillable
        // so write sites can set it explicitly as a belt-and-braces alongside
        // save()'s authoritative recompute - see save() below. Every call site
        // passes a fixed key array (verified: no request-input spreading
        // anywhere in this package), so this carries no mass-assignment risk,
        // and save() overwrites any caller-supplied value regardless.
        'group_key',
        'type',
        'text',
        'is_vendor',
        'vendor',
    ];

    /**
     * Guards cache:clear calls during bulk sync operations.
     * Set to true by syncToDatabase() and restored in a finally block.
     */
    private static bool $syncing = false;

    private const COUNTS_MEMO_KEY = 'lingua.translation-counts';

    /**
     * Collects entries touched during syncToDatabase() for targeted cache invalidation.
     * Each entry: [locale, group, isVendor (bool), vendor (string|null)]
     *
     * @var array<int, array{0: string, 1: string, 2: bool, 3: string|null}>
     */
    private static array $touchedCacheKeys = [];

    /**
     * Bootstrap the model and its traits.
     * Automatically generates UUID for new translations.
     */
    public static function boot(): void
    {
        parent::boot();
        // Suppress per-row cache forgets during bulk sync; syncToDatabase() forgets each touched key once in its finally block.
        static::saved(function (self $model) {
            app(RequestMemo::class)->forget(self::COUNTS_MEMO_KEY);

            if (! self::$syncing) {
                $model->forgetCacheForLocales();
            }
        });
        static::deleted(function (self $model) {
            app(RequestMemo::class)->forget(self::COUNTS_MEMO_KEY);

            $model->forgetCacheForLocales();
        });
    }

    /**
     * Persist the model, guaranteeing group_key is populated even when Eloquent
     * events are unavailable (e.g. the event dispatcher is unbound while this
     * model first boots during `artisan migrate:fresh --seed`, or events are
     * suppressed via withoutEvents()/saveQuietly()). A previous fix relied on a
     * saving() event hook alone - that hook silently never registers when the
     * dispatcher isn't bound yet, leaving group_key absent from the INSERT and
     * violating the NOT NULL + UNIQUE constraint. group_key must never depend
     * on the event pipeline, so it is recomputed here, in the write path
     * itself, before every persist.
     */
    public function save(array $options = []): bool
    {
        $this->group_key = self::buildGroupKey(
            $this->group,
            $this->key,
            $this->is_vendor ?? false,
            $this->vendor,
        );

        return parent::save($options);
    }

    /**
     * Retrieve all app (non-vendor) translations for the given locale and group, caching the result forever.
     *
     * Vendor rows are excluded: they are served by getVendorTranslationsForGroup() under their
     * own namespace, and a vendor group with the same name must not leak into the app group.
     *
     * @return array<string, mixed>
     */
    public static function getTranslationsForGroup(string $locale, string $group): array
    {
        $resolve = fn (): array => static::where('is_vendor', false)
            ->where('group', $group)
            ->get()
            ->reduce(function (array $carry, self $translation) use ($locale, $group): array {
                $value = $translation->text[$locale] ?? null;
                if ($value !== null) {
                    // JSON keys are sentences that may contain dots: keep them whole.
                    if ($group === 'single') {
                        $carry[$translation->key] = $value;
                    } else {
                        Arr::set($carry, $translation->key, $value);
                    }
                }

                return $carry;
            }, []);

        // Never forever-cache an attacker-controlled, malformed locale: that
        // would let a request-driven locale grow the cache without bound.
        if (! self::isWellFormedLocale($locale)) {
            return $resolve();
        }

        return self::rememberForeverResilient(CacheKey::forGroup($locale, $group), $resolve);
    }

    /**
     * Retrieve all vendor translations for the given locale, vendor, and group, caching forever.
     *
     * Mirrors getTranslationsForGroup() but scoped to is_vendor=true rows for a specific
     * vendor namespace. Uses a distinct cache key (forVendorGroup) to avoid collisions
     * with app-string groups of the same name.
     *
     * @return array<string, mixed>
     */
    public static function getVendorTranslationsForGroup(string $locale, string $vendor, string $group): array
    {
        $resolve = fn (): array => static::where('is_vendor', true)
            ->where('vendor', $vendor)
            ->where('group', $group)
            ->get()
            ->reduce(function (array $carry, self $translation) use ($locale): array {
                $value = $translation->text[$locale] ?? null;
                if ($value !== null) {
                    Arr::set($carry, $translation->key, $value);
                }

                return $carry;
            }, []);

        // Never forever-cache an attacker-controlled, malformed locale.
        if (! self::isWellFormedLocale($locale)) {
            return $resolve();
        }

        return self::rememberForeverResilient(CacheKey::forVendorGroup($locale, $vendor, $group), $resolve);
    }

    /**
     * rememberForever() that degrades to the database when the cache store is unavailable.
     *
     * Only cache I/O is guarded: the read and the write-back after a miss. A failure is
     * reported, opens the request-scoped CacheCircuit and the value is served from the
     * database. The resolver is deliberately NOT guarded: database exceptions must
     * propagate unchanged, because LinguaManager::load() relies on QueryException for its
     * missing-table fallback. The store itself is resolved outside any try block, so an
     * invalid store name stays a loud misconfiguration error.
     *
     * @param  Closure(): array<string, mixed>  $resolve
     * @return array<string, mixed>
     */
    private static function rememberForeverResilient(string $key, Closure $resolve): array
    {
        $store = Cache::store(config('lingua.cache.store'));
        $circuit = app(CacheCircuit::class);

        if ($circuit->isOpen()) {
            return $resolve();
        }

        try {
            $cached = $store->get($key);
        } catch (Throwable $e) {
            $circuit->open();
            report($e);

            return $resolve();
        }

        if ($cached !== null) {
            return $cached;
        }

        $value = $resolve();

        // A group that resolves to no rows is not cached: request-derived group names
        // would otherwise grow the cache forever.
        if ($value === []) {
            return $value;
        }

        try {
            $store->forever($key, $value);
        } catch (Throwable $e) {
            $circuit->open();
            report($e);
        }

        return $value;
    }

    /**
     * Whether a locale code matches the canonical, safe-to-cache shape.
     *
     * Gates rememberForever() so that request-derived locales cannot create
     * unbounded forever-cached entries (DoS). Garbage locales still resolve
     * correctly - they are simply never written to the cache.
     */
    private static function isWellFormedLocale(string $locale): bool
    {
        return (bool) preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*\z/', $locale);
    }

    /**
     * Forget all cache keys of this row, for every locale present in its current and original text.
     *
     * The keys of the current identity and of the original one (group, vendor flag, vendor) are both
     * forgotten, so renaming a row also clears the group it left. The forget runs now and once more
     * after the surrounding transaction commits: a read inside the transaction can re-cache the value
     * that is about to be committed or rolled back. Outside a transaction the callback runs at once.
     * Vendor rows bust forVendorGroup(); app rows bust forGroup().
     */
    protected function forgetCacheForLocales(): void
    {
        $locales = array_unique(array_merge(
            array_keys($this->text ?? []),
            array_keys($this->getOriginal('text') ?? [])
        ));

        $identities = [
            [$this->group, (bool) $this->is_vendor, $this->vendor],
            [
                $this->getOriginal('group') ?? $this->group,
                (bool) ($this->getOriginal('is_vendor') ?? $this->is_vendor),
                $this->getOriginal('vendor') ?? $this->vendor,
            ],
        ];

        $keys = [];
        foreach ($identities as [$group, $isVendor, $vendor]) {
            foreach ($locales as $locale) {
                $keys[] = $isVendor && $vendor
                    ? CacheKey::forVendorGroup((string) $locale, $vendor, $group)
                    : CacheKey::forGroup((string) $locale, $group);
            }
        }

        $keys = array_values(array_unique($keys));

        $forget = static function () use ($keys): void {
            $store = Cache::store(config('lingua.cache.store'));

            foreach ($keys as $key) {
                $store->forget($key);
            }
        };

        $forget();
        $this->getConnection()->afterCommit($forget);
    }

    protected function groupKey(): Attribute
    {
        // Population lives solely in the saving() hook above - this accessor
        // only normalizes the stored value on read.
        return Attribute::make(
            get: fn ($value) => Str::squish($value),
        );
    }

    public static function buildGroupKey(string $group, string $key, bool $isVendor, ?string $vendor): string
    {
        $prefix = $isVendor && $vendor ? $vendor.'::' : '';

        return Str::wrap('.', before: $prefix.Str::squish($group), after: Str::squish($key));
    }

    public function getLangKeyAttribute(): string
    {
        return self::buildGroupKey($this->group, $this->key, $this->is_vendor ?? false, $this->vendor);
    }

    /**
     * Set the translation value for a specific locale and persist.
     *
     * @param  string  $locale  The locale code
     * @param  string  $value  The translated string
     */
    public function setTranslation(string $locale, string $value): static
    {
        $this->text = array_merge($this->text ?? [], [$locale => $value]);
        $this->save();

        return $this;
    }

    /**
     * Remove translation for a specific locale.
     *
     * @param  string  $locale  The locale code to remove
     */
    public function forgetTranslation(string $locale): void
    {
        // Mirror the repository-layer guard so the integrity invariant holds
        // even when this method is called directly (e.g. RemoveLangCommand).
        if ($this->is_vendor) {
            throw new VendorTranslationProtectedException;
        }

        $data = $this->text;
        unset($data[$locale]);
        $this->text = $data;
        $this->save();
    }

    public static function syncToLocal(): void
    {
        $writer = app(AtomicFileWriter::class);
        $languages = Language::orderBy('sort', 'desc')->get();
        $translations = self::all();
        $langPath = config('lingua.lang_dir') ?: lang_path();
        $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT;

        foreach ($languages as $language) {
            $locale = $language->code;
            PathGuard::assertSafeSegment($locale, 'locale');

            // Values per owner ('' = the app, otherwise the vendor name), group and key.
            $owners = ['' => []];

            foreach ($translations as $translation) {
                $text = $translation->text;
                if (! isset($text[$locale])) {
                    continue;
                }

                $owner = $translation->is_vendor ? ($translation->vendor ?: 'vendor') : '';
                $owners[$owner][$translation->group][$translation->key] = $text[$locale];
            }

            foreach ($owners as $owner => $groups) {
                $owner = (string) $owner;
                $root = $langPath;

                if ($owner !== '') {
                    PathGuard::assertSafeSegment($owner, 'vendor');
                    $root = $langPath.'/vendor/'.$owner;
                }

                // The core JSON is always written so the locale stays discoverable; vendor JSON only with single rows.
                if ($owner === '' || isset($groups['single'])) {
                    $jsonFile = $root.'/'.$locale.'.json';
                    $existing = [];
                    if (file_exists($jsonFile)) {
                        $decoded = json_decode((string) file_get_contents($jsonFile), true);
                        $existing = is_array($decoded) ? $decoded : [];
                    }

                    $writer->ensureDir($root);
                    $writer->putJson($jsonFile, array_replace($existing, $groups['single'] ?? []), $jsonFlags);
                }

                foreach ($groups as $group => $values) {
                    $group = (string) $group;
                    if ($group === 'single') {
                        continue;
                    }

                    PathGuard::assertSafeSegment($group, $owner === '' ? 'group' : 'vendor group');
                    $folder = $root.'/'.$locale;
                    $writer->ensureDir($folder);

                    $phpFile = $folder.'/'.$group.'.php';
                    $existing = [];
                    if (file_exists($phpFile)) {
                        $loaded = include $phpFile;
                        $existing = is_array($loaded) ? $loaded : [];
                    }

                    foreach ($values as $key => $value) {
                        DottedKeyArray::set($existing, (string) $key, $value);
                    }

                    $writer->putPhp($phpFile, "<?php\n\nreturn ".PhpArrayExporter::export($existing).";\n");
                }
            }
        }
    }

    /**
     * Synchronize local translation files to the database.
     *
     * Implements a two-pass strategy:
     *   Pass 1 - Default locale: bundled base translations are merged first, then
     *             app lang files (app files override bundled values for the same
     *             key); all keys are imported unconditionally and the set of
     *             group+key combinations becomes the reference for Pass 2.
     *   Pass 2 - Remaining locales:
     *             • Bundled base translations are merged only for locales that are
     *               INSTALLED (a Language record exists) - adding a language and
     *               re-syncing imports its bundled dataset, while never installing
     *               the other bundled locales implicitly.
     *             • Non-vendor keys are imported only if the same key exists in the
     *               default locale files (orphan keys are silently skipped).
     *             • Vendor keys are imported only if a Language record exists for
     *               the locale at the time the vendor sub-pass runs.
     *
     * Existing DB records are never deleted; removals in lang files are ignored.
     * The model's saved() cache:clear hook is suppressed during bulk writes and
     * replaced by a single cache:clear at the end of the operation.
     *
     * Performance: existing rows are loaded ONCE into an in-memory index and only
     * rows whose value actually changes are written, all inside one transaction.
     * The previous per-entry lookup (two SELECTs + one write per key per locale)
     * was latency-bound: a few thousand round-trips to a remote database exceeded
     * reverse-proxy timeouts (e.g. Cloudflare 100 s) when triggered from the UI.
     */
    public static function syncToDatabase(): void
    {
        $langPath = config('lingua.lang_dir') ?: lang_path();

        $defaultLocale = Language::default()?->code ?? config('lingua.default_locale', 'en');

        $bundledSource = app(BaseTranslationSource::class);
        $reader = app(TranslationFileReader::class);

        $allLocales = array_values(array_unique(array_merge(
            $reader->discoverLocales($langPath),
            $bundledSource->available()
        )));

        $remainingLocales = array_values(array_diff($allLocales, [$defaultLocale]));

        // Pre-cache Language codes currently in DB so vendor key filtering
        // can be updated incrementally as new records are created in Pass 2.
        $installedCodes = Language::pluck('code')->flip()->all();

        // Locales removed on purpose are never recreated from lang files.
        $removedLocales = array_flip(LinguaSetting::removedLocales());

        self::$syncing = true;

        try {
            self::query()->getConnection()->transaction(function () use (
                $bundledSource, $reader, $langPath, $defaultLocale, $remainingLocales, $removedLocales, &$installedCodes
            ): void {
                // One query for every existing row; writeTranslation() resolves against it.
                $index = [];
                foreach (self::query()->get() as $row) {
                    $index[self::indexKey($row->group, $row->key, $row->is_vendor, $row->vendor)] = $row;
                }

                // ─── Pass 1: Default locale ────────────────────────────────────────────
                $defaultTranslations = array_merge(
                    $bundledSource->translationsFor($defaultLocale),
                    $reader->collect($langPath, $defaultLocale)
                );
                self::ensureLanguageRecord($defaultLocale);
                $installedCodes[$defaultLocale] = true;

                $defaultKeys = [];
                foreach ($defaultTranslations as $translation) {
                    if (! $translation['is_vendor']) {
                        $composite = $translation['group'].'|'.$translation['key'].'|0|';
                        $defaultKeys[$composite] = true;
                    }
                    self::writeTranslation($translation, $defaultLocale, $index);
                }

                // ─── Pass 2: Remaining locales ─────────────────────────────────────────
                foreach ($remainingLocales as $locale) {
                    if (isset($removedLocales[$locale])) {
                        continue;
                    }

                    // Bundled content only for INSTALLED locales - never auto-install
                    // the whole bundled catalogue. Lang files always participate;
                    // they are appended after bundled entries so app files override
                    // bundled values for the same key.
                    $translations = array_merge(
                        isset($installedCodes[$locale]) ? $bundledSource->translationsFor($locale) : [],
                        $reader->collect($langPath, $locale)
                    );
                    $localeEnsured = false;

                    // Sub-pass A: Non-vendor keys - must exist in default locale key set.
                    foreach ($translations as $translation) {
                        if ($translation['is_vendor']) {
                            continue;
                        }

                        $composite = $translation['group'].'|'.$translation['key'].'|0|';

                        if (! isset($defaultKeys[$composite])) {
                            if (config('app.debug')) {
                                Log::debug('Lingua sync: skipping orphan key', [
                                    'group' => $translation['group'],
                                    'key' => $translation['key'],
                                    'locale' => $locale,
                                ]);
                            }

                            continue;
                        }

                        if (! $localeEnsured) {
                            self::ensureLanguageRecord($locale);
                            $installedCodes[$locale] = true;
                            $localeEnsured = true;
                        }

                        self::writeTranslation($translation, $defaultLocale, $index);
                    }

                    // Sub-pass B: Vendor keys - require Language record to exist.
                    if (! isset($installedCodes[$locale])) {
                        if (config('app.debug')) {
                            $skipped = array_filter($translations, fn (array $t): bool => $t['is_vendor']);
                            if ($skipped !== []) {
                                Log::debug('Lingua sync: skipping vendor keys - locale not installed', [
                                    'locale' => $locale,
                                    'count' => count($skipped),
                                ]);
                            }
                        }

                        continue;
                    }

                    foreach ($translations as $translation) {
                        if (! $translation['is_vendor']) {
                            continue;
                        }
                        self::writeTranslation($translation, $defaultLocale, $index);
                    }
                }
            });
        } finally {
            self::$syncing = false;
            app(RequestMemo::class)->forget(self::COUNTS_MEMO_KEY);
            $store = Cache::store(config('lingua.cache.store'));
            foreach (array_unique(self::$touchedCacheKeys, SORT_REGULAR) as [$locale, $group, $isVendor, $vendor]) {
                if ($isVendor && $vendor) {
                    $store->forget(CacheKey::forVendorGroup($locale, $vendor, $group));
                } else {
                    $store->forget(CacheKey::forGroup($locale, $group));
                }
            }
            self::$touchedCacheKeys = [];
        }
    }

    /**
     * Create or update the Language record for a given locale using Locales metadata.
     *
     * Called once per locale during sync - never per translation row.
     * Only applicable to non-vendor locales that have qualifying keys.
     *
     * @param  string  $locale  ISO locale code.
     */
    private static function ensureLanguageRecord(string $locale): void
    {
        $localeInfo = app(LocaleRegistry::class)->info($locale);

        if ($localeInfo === null) {
            Language::firstOrCreate(['code' => $locale], [
                'regional' => null,
                'type' => 'Latn',
                'name' => $locale,
                'native' => $locale,
                'direction' => 'ltr',
            ]);

            return;
        }

        Language::updateOrCreate(
            [
                'code' => $localeInfo->code,
                'regional' => $localeInfo->regional,
            ],
            [
                'type' => $localeInfo->type,
                'name' => $localeInfo->name,
                'native' => $localeInfo->native,
                'direction' => $localeInfo->direction,
            ]
        );
    }

    /**
     * Persist a single translation entry to the database.
     *
     * Merges the new locale value into the existing text JSON column rather than
     * replacing it, preserving translations for all other locales.
     * Detects LinguaType (text/html/markdown) only for default-locale values;
     * for all other locales the existing type is preserved.
     *
     * The default locale is passed explicitly: comparing against
     * linguaDefaultLocale() (the app fallback locale) diverged from the
     * Language::default() value resolved by syncToDatabase(), silently
     * disabling type detection whenever the two differed.
     *
     * @param  array{locale: string, group: string, key: string, value: string, is_vendor: bool, vendor: string|null}  $translation
     * @param  string  $defaultLocale  The default locale resolved by the caller.
     * @param  array<string, self>  $index  Existing rows keyed by indexKey(); new rows are added to it.
     */
    private static function writeTranslation(array $translation, string $defaultLocale, array &$index): void
    {
        $indexKey = self::indexKey(
            (string) $translation['group'],
            (string) $translation['key'],
            (bool) $translation['is_vendor'],
            $translation['vendor'],
        );
        $existing = $index[$indexKey] ?? null;

        // Cache keys are touched even when nothing is written, preserving the
        // "sync refreshes the cache" contract; the finally block deduplicates them.
        self::$touchedCacheKeys[] = [
            $translation['locale'],
            $translation['group'],
            (bool) $translation['is_vendor'],
            $translation['vendor'] ?? null,
        ];

        if ($existing !== null) {
            // Type is decided only when a row is created (unchanged behaviour),
            // so an existing row needs a write only when its locale value differs.
            if (array_key_exists($translation['locale'], $existing->text ?? [])
                && $existing->text[$translation['locale']] === $translation['value']) {
                return;
            }

            $existing->text = array_merge(
                $existing->text ?? [],
                [$translation['locale'] => $translation['value']]
            );
            $existing->save();

            return;
        }

        $stringType = LinguaType::text;

        // Skip type detection on oversized values: it is not worth the
        // regex cost and bounds the work an attacker can trigger.
        if ($translation['locale'] === $defaultLocale && mb_strlen((string) $translation['value']) <= 10000) {
            $string = Str::of($translation['value'])->trim();
            if (preg_match('#(?<=<)\w+(?=[^<]*?>)#', $string->toString())) {
                $stringType = LinguaType::html;
            }
            // Detect markdown only when no HTML was found - look for common markdown markers.
            // The link branch uses a negated class ([^\]]+) to avoid ReDoS backtracking.
            if ($stringType === LinguaType::text &&
                preg_match('/^#{1,6}\s|\n#{1,6}\s|^\s*[-*+]\s|\[[^\]]+\]\(https?:/im', $string->toString())) {
                $stringType = LinguaType::markdown;
            }
        }

        // save() recomputes group_key; group/key are cast to string because
        // TranslationFileReader::flatten() can yield an int key for a top-level
        // numeric-indexed array entry, which the strictly-typed buildGroupKey()
        // would reject.
        $row = new self([
            'group' => (string) $translation['group'],
            'key' => (string) $translation['key'],
            'is_vendor' => $translation['is_vendor'],
            'vendor' => $translation['vendor'],
            'type' => $stringType,
            'text' => [$translation['locale'] => $translation['value']],
        ]);
        $row->save();

        $index[$indexKey] = $row;
    }

    /**
     * Composite identity of a translation row, used by the in-memory sync index.
     *
     * Mirrors the (group, key, is_vendor, vendor) lookup previously done in SQL.
     */
    private static function indexKey(string $group, string $key, bool $isVendor, ?string $vendor): string
    {
        return $group."\0".$key."\0".($isVendor ? '1' : '0')."\0".($vendor ?? '');
    }

    /**
     * Count all translations that have a non-null value for the given locale.
     *
     * Uses PHP aggregation over the text JSON column to stay compatible with
     * every supported database driver (SQLite, MySQL, PostgreSQL, SQL Server).
     */
    public static function countByLocale(string $locale): int
    {
        return static::translationCounts()['byLocale'][$locale] ?? 0;
    }

    /**
     * Get translation statistics for a specific locale.
     *
     * @return array{total: int, translated: int, missing: int, percentage: float|int}
     */
    public static function getLocaleStats(string $locale): array
    {
        $counts = static::translationCounts();
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
     * Load all language_lines rows (text column only) and count per-locale in PHP.
     *
     * Returns ['total' => int, 'byLocale' => [locale => count]].
     * No SQL JSON functions used - compatible with all supported drivers.
     *
     * @return array{total: int, byLocale: array<string, int>}
     */
    public static function translationCounts(): array
    {
        return app(RequestMemo::class)->remember(self::COUNTS_MEMO_KEY, function (): array {
            $byLocale = [];
            $total = 0;

            self::select('text')->each(function (self $row) use (&$byLocale, &$total): void {
                $total++;
                foreach (array_keys($row->text ?? []) as $locale) {
                    $byLocale[$locale] = ($byLocale[$locale] ?? 0) + 1;
                }
            });

            return ['total' => $total, 'byLocale' => $byLocale];
        });
    }
}
