<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\Factories\TranslationFactory;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Locales\LocaleRegistry;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\PathGuard;
use Rivalex\Lingua\Support\PhpArrayExporter;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\TranslationManager\CacheKey;

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
        static::creating(function ($model) {
            $model->group_key = self::buildGroupKey(
                $model->group,
                $model->key,
                $model->is_vendor ?? false,
                $model->vendor
            );
        });
        static::saving(function ($model) {
            if ($model->isDirty('group') || $model->isDirty('key') || $model->isDirty('is_vendor') || $model->isDirty('vendor')) {
                $model->group_key = self::buildGroupKey(
                    $model->group,
                    $model->key,
                    $model->is_vendor ?? false,
                    $model->vendor
                );
            }
        });
        // Suppress per-row cache clears during bulk sync; a single clear fires in the finally block.
        static::saved(function (self $model) {
            if (! self::$syncing) {
                $model->forgetCacheForLocales();
            }
        });
        static::deleted(function (self $model) {
            $model->forgetCacheForLocales();
        });
    }

    /**
     * Retrieve all translations for the given locale and group, caching the result forever.
     *
     * @return array<string, mixed>
     */
    public static function getTranslationsForGroup(string $locale, string $group): array
    {
        $resolve = fn (): array => static::where('group', $group)
            ->get()
            ->reduce(function (array $carry, self $translation) use ($locale): array {
                $value = $translation->text[$locale] ?? null;
                if ($value !== null) {
                    data_set($carry, $translation->key, $value);
                }

                return $carry;
            }, []);

        // Never forever-cache an attacker-controlled, malformed locale: that
        // would let a request-driven locale grow the cache without bound.
        if (! self::isWellFormedLocale($locale)) {
            return $resolve();
        }

        return Cache::store(config('lingua.cache.store'))->rememberForever(
            CacheKey::forGroup($locale, $group),
            $resolve
        );
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
                    data_set($carry, $translation->key, $value);
                }

                return $carry;
            }, []);

        // Never forever-cache an attacker-controlled, malformed locale.
        if (! self::isWellFormedLocale($locale)) {
            return $resolve();
        }

        return Cache::store(config('lingua.cache.store'))->rememberForever(
            CacheKey::forVendorGroup($locale, $vendor, $group),
            $resolve
        );
    }

    /**
     * Whether a locale code matches the canonical, safe-to-cache shape.
     *
     * Gates rememberForever() so that request-derived locales cannot create
     * unbounded forever-cached entries (DoS). Garbage locales still resolve
     * correctly — they are simply never written to the cache.
     */
    private static function isWellFormedLocale(string $locale): bool
    {
        return (bool) preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*$/', $locale);
    }

    /**
     * Forget all cache keys for every locale present in this model's text column.
     * Unions current and original text to catch locale removals.
     * Vendor rows bust forVendorGroup(); app rows bust forGroup().
     */
    protected function forgetCacheForLocales(): void
    {
        $locales = array_unique(array_merge(
            array_keys($this->text ?? []),
            array_keys($this->getOriginal('text') ?? [])
        ));

        $store = Cache::store(config('lingua.cache.store'));

        foreach ($locales as $locale) {
            if ($this->is_vendor && $this->vendor) {
                $store->forget(CacheKey::forVendorGroup($locale, $this->vendor, $this->group));
            } else {
                $store->forget(CacheKey::forGroup($locale, $this->group));
            }
        }
    }

    protected function groupKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Str::squish($value),
            set: fn () => self::buildGroupKey(
                $this->group,
                $this->key,
                $this->is_vendor ?? false,
                $this->vendor,
            )
        );
    }

    protected static function buildGroupKey(string $group, string $key, bool $isVendor, ?string $vendor): string
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
        $langPath = config('lingua.lang_dir');

        foreach ($languages as $language) {
            $locale = $language->code;
            PathGuard::assertSafeSegment($locale, 'locale');

            $coreGroups = [];
            $vendorGroups = [];

            foreach ($translations as $translation) {
                $text = $translation->text;
                if (! isset($text[$locale])) {
                    continue;
                }

                if ($translation->is_vendor) {
                    $vendor = $translation->vendor ?? 'vendor';
                    if ($translation->group === 'single') {
                        $vendorGroups[$vendor]['single'][$translation->key] = $text[$locale];
                    } else {
                        data_set($vendorGroups[$vendor][$translation->group], $translation->key, $text[$locale]);
                    }
                } else {
                    if ($translation->group === 'single') {
                        $coreGroups['single'][$translation->key] = $text[$locale];
                    } else {
                        data_set($coreGroups[$translation->group], $translation->key, $text[$locale]);
                    }
                }
            }

            // Core JSON
            $jsonFile = $langPath.'/'.$locale.'.json';
            $writer->putJson(
                $jsonFile,
                $coreGroups['single'] ?? [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

            // Core PHP
            foreach ($coreGroups as $group => $groupTranslations) {
                if ($group === 'single') {
                    continue;
                }

                PathGuard::assertSafeSegment($group, 'group');
                $langFolder = $langPath.'/'.$locale;
                $writer->ensureDir($langFolder);

                $phpFile = $langFolder.'/'.$group.'.php';
                $writer->putPhp($phpFile, "<?php\n\nreturn ".PhpArrayExporter::export($groupTranslations).";\n");
            }

            // Vendor JSON + PHP
            foreach ($vendorGroups as $vendor => $groups) {
                PathGuard::assertSafeSegment($vendor, 'vendor');
                $vendorRoot = $langPath.'/vendor/'.$vendor;

                if (isset($groups['single'])) {
                    $writer->ensureDir($vendorRoot);
                    $jsonFile = $vendorRoot.'/'.$locale.'.json';
                    $writer->putJson(
                        $jsonFile,
                        $groups['single'],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    );
                }

                foreach ($groups as $group => $groupTranslations) {
                    if ($group === 'single') {
                        continue;
                    }

                    PathGuard::assertSafeSegment($group, 'vendor group');
                    $vendorLangFolder = $vendorRoot.'/'.$locale;
                    $writer->ensureDir($vendorLangFolder);

                    $phpFile = $vendorLangFolder.'/'.$group.'.php';
                    $writer->putPhp($phpFile, "<?php\n\nreturn ".PhpArrayExporter::export($groupTranslations).";\n");
                }
            }
        }
    }

    /**
     * Synchronize local translation files to the database.
     *
     * Implements a two-pass strategy:
     *   Pass 1 — Default locale: bundled base translations are merged first, then
     *             app lang files (app files override bundled values for the same
     *             key); all keys are imported unconditionally and the set of
     *             group+key combinations becomes the reference for Pass 2.
     *   Pass 2 — Remaining locales:
     *             • Bundled base translations are merged only for locales that are
     *               INSTALLED (a Language record exists) — adding a language and
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
     */
    public static function syncToDatabase(): void
    {
        $langPath = config('lingua.lang_dir');

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

        self::$syncing = true;

        try {
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
                self::writeTranslation($translation, $defaultLocale);
            }

            // ─── Pass 2: Remaining locales ─────────────────────────────────────────
            foreach ($remainingLocales as $locale) {
                // Bundled content only for INSTALLED locales — never auto-install
                // the whole bundled catalogue. Lang files always participate;
                // they are appended after bundled entries so app files override
                // bundled values for the same key.
                $translations = array_merge(
                    isset($installedCodes[$locale]) ? $bundledSource->translationsFor($locale) : [],
                    $reader->collect($langPath, $locale)
                );
                $localeEnsured = false;

                // Sub-pass A: Non-vendor keys — must exist in default locale key set.
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

                    self::writeTranslation($translation, $defaultLocale);
                }

                // Sub-pass B: Vendor keys — require Language record to exist.
                if (! isset($installedCodes[$locale])) {
                    if (config('app.debug')) {
                        $skipped = array_filter($translations, fn (array $t): bool => $t['is_vendor']);
                        if ($skipped !== []) {
                            Log::debug('Lingua sync: skipping vendor keys — locale not installed', [
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
                    self::writeTranslation($translation, $defaultLocale);
                }
            }
        } finally {
            self::$syncing = false;
            $store = Cache::store(config('lingua.cache.store'));
            foreach (self::$touchedCacheKeys as [$locale, $group, $isVendor, $vendor]) {
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
     * Called once per locale during sync — never per translation row.
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
     */
    private static function writeTranslation(array $translation, string $defaultLocale): void
    {
        $existing = self::where('group', $translation['group'])
            ->where('key', $translation['key'])
            ->where('is_vendor', $translation['is_vendor'])
            ->where('vendor', $translation['vendor'])
            ->first();

        $stringType = LinguaType::text;

        // Skip type detection on oversized values: it is not worth the
        // regex cost and bounds the work an attacker can trigger.
        if ($translation['locale'] === $defaultLocale && mb_strlen((string) $translation['value']) <= 10000) {
            $string = Str::of($translation['value'])->trim();
            if (preg_match('#(?<=<)\w+(?=[^<]*?>)#', $string->toString())) {
                $stringType = LinguaType::html;
            }
            // Detect markdown only when no HTML was found — look for common markdown markers.
            // The link branch uses a negated class ([^\]]+) to avoid ReDoS backtracking.
            if ($stringType === LinguaType::text &&
                preg_match('/^#{1,6}\s|\n#{1,6}\s|^\s*[-*+]\s|\[[^\]]+\]\(https?:/im', $string->toString())) {
                $stringType = LinguaType::markdown;
            }
        }

        Translation::updateOrCreate(
            [
                'group' => $translation['group'],
                'key' => $translation['key'],
                'is_vendor' => $translation['is_vendor'],
                'vendor' => $translation['vendor'],
            ],
            [
                'type' => $existing->type ?? $stringType,
                'text' => array_merge(
                    $existing->text ?? [],
                    [$translation['locale'] => $translation['value']]
                ),
            ]
        );

        self::$touchedCacheKeys[] = [
            $translation['locale'],
            $translation['group'],
            (bool) $translation['is_vendor'],
            $translation['vendor'] ?? null,
        ];
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
     * No SQL JSON functions used — compatible with all supported drivers.
     *
     * @return array{total: int, byLocale: array<string, int>}
     */
    public static function translationCounts(): array
    {
        $byLocale = [];
        $total = 0;

        self::select('text')->each(function (self $row) use (&$byLocale, &$total): void {
            $total++;
            foreach (array_keys($row->text ?? []) as $locale) {
                $byLocale[$locale] = ($byLocale[$locale] ?? 0) + 1;
            }
        });

        return ['total' => $total, 'byLocale' => $byLocale];
    }
}
