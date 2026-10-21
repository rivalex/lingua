<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Database\Factories\TranslationFactory;
use Rivalex\Lingua\Enums\LinguaType;
use Spatie\TranslationLoader\LanguageLine;

/**
 * Class Translation
 * Package: App\Models\System
 *
 * Translation model for managing language translations in the application.
 * Extends LanguageLine to provide advanced translation management capabilities.
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
class Translation extends LanguageLine
{
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
        static::saved(function () {
            if (! self::$syncing) {
                Artisan::call('cache:clear');
            }
        });
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

    public static function getGroupKey(string $group, string $key, bool $isVendor, ?string $vendor): string
    {
        return static::buildGroupKey($group, $key, $isVendor, $vendor);
    }

    public function getLangKeyAttribute(): string
    {
        return self::buildGroupKey($this->group, $this->key, $this->is_vendor ?? false, $this->vendor);
    }

    /**
     * Remove translation for a specific locale.
     *
     * @param  string  $locale  The locale code to remove
     */
    public function forgetTranslation(string $locale): void
    {
        $data = $this->text;
        unset($data[$locale]);
        $this->text = $data;
        $this->save();
    }

    /**
     * Synchronize database translations to local files.
     * Generates JSON and PHP translation files for each language.
     */
    public static function syncToLocal(): void
    {
        $languages = Language::orderBy('sort', 'desc')->get();
        $translations = self::all();
        $langPath = config('lingua.lang_dir');

        foreach ($languages as $language) {
            $locale = $language->code;

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
            file_put_contents($jsonFile,
                json_encode($coreGroups['single'] ?? (object) [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Core PHP
            foreach ($coreGroups as $group => $groupTranslations) {
                if ($group === 'single') {
                    continue;
                }

                $langFolder = $langPath.'/'.$locale;
                if (! is_dir($langFolder)) {
                    mkdir($langFolder, 0755, true);
                }

                $phpFile = $langFolder.'/'.$group.'.php';
                $content = "<?php\n\nreturn ".self::exportArray($groupTranslations).";\n";
                file_put_contents($phpFile, $content);
            }

            // Vendor JSON + PHP
            foreach ($vendorGroups as $vendor => $groups) {
                $vendorRoot = $langPath.'/vendor/'.$vendor;

                if (isset($groups['single'])) {
                    if (! is_dir($vendorRoot)) {
                        mkdir($vendorRoot, 0755, true);
                    }
                    $jsonFile = $vendorRoot.'/'.$locale.'.json';
                    file_put_contents($jsonFile,
                        json_encode($groups['single'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                foreach ($groups as $group => $groupTranslations) {
                    if ($group === 'single') {
                        continue;
                    }

                    $vendorLangFolder = $vendorRoot.'/'.$locale;
                    if (! is_dir($vendorLangFolder)) {
                        mkdir($vendorLangFolder, 0755, true);
                    }

                    $phpFile = $vendorLangFolder.'/'.$group.'.php';
                    $content = "<?php\n\nreturn ".self::exportArray($groupTranslations).";\n";
                    file_put_contents($phpFile, $content);
                }
            }
        }
    }

    /**
     * Export array to PHP code string format.
     *
     * @param  array  $array  Array to be exported
     * @param  string  $indent  Current indentation level
     * @return string PHP code representation of the array
     */
    protected static function exportArray(array $array, string $indent = ''): string
    {
        $content = "[\n";
        foreach ($array as $key => $value) {
            $content .= $indent.'    '.var_export($key, true).' => ';
            if (is_array($value)) {
                $content .= self::exportArray($value, $indent.'    ');
            } else {
                $content .= var_export($value, true);
            }
            $content .= ",\n";
        }
        $content .= $indent.']';

        return $content;
    }

    /**
     * Synchronize local translation files to the database.
     *
     * Implements a two-pass strategy:
     *   Pass 1 — Default locale: all keys are imported unconditionally; the set of
     *             group+key combinations becomes the reference for Pass 2.
     *   Pass 2 — Remaining locales:
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

        if (Locales::installed()->count() === 0) {
            Artisan::call('lang:add '.config('lingua.default_locale'));
        }

        $defaultLocale = Language::default()?->code ?? config('lingua.default_locale', 'en');

        $allLocales = array_values(array_unique(array_merge(
            self::discoverLocales($langPath),
            Locales::installed()->pluck('code')->all()
        )));

        $remainingLocales = array_values(array_diff($allLocales, [$defaultLocale]));

        // Pre-cache Language codes currently in DB so vendor key filtering
        // can be updated incrementally as new records are created in Pass 2.
        $installedCodes = Language::pluck('code')->flip()->all();

        self::$syncing = true;

        try {
            // ─── Pass 1: Default locale ────────────────────────────────────────────
            $defaultTranslations = self::collectLocaleTranslations($langPath, $defaultLocale);
            self::ensureLanguageRecord($defaultLocale);
            $installedCodes[$defaultLocale] = true;

            $defaultKeys = [];
            foreach ($defaultTranslations as $translation) {
                if (! $translation['is_vendor']) {
                    $composite = $translation['group'].'|'.$translation['key'].'|0|';
                    $defaultKeys[$composite] = true;
                }
                self::writeTranslation($translation);
            }

            // ─── Pass 2: Remaining locales ─────────────────────────────────────────
            foreach ($remainingLocales as $locale) {
                $translations = self::collectLocaleTranslations($langPath, $locale);
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

                    self::writeTranslation($translation);
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
                    self::writeTranslation($translation);
                }
            }
        } finally {
            self::$syncing = false;
            Artisan::call('cache:clear');
        }
    }

    /**
     * Collect all translation entries for a single locale from the filesystem.
     *
     * Reads core JSON, core PHP, vendor JSON, and vendor PHP files in order.
     * Returns a flat array; each item has keys:
     *   locale, group, key, value, is_vendor, vendor.
     *
     * @param  string  $langPath  Absolute path to the lang directory.
     * @param  string  $locale  ISO locale code to collect files for.
     * @return array<int, array{locale: string, group: string, key: string, value: string, is_vendor: bool, vendor: string|null}>
     */
    private static function collectLocaleTranslations(string $langPath, string $locale): array
    {
        $result = [];

        // 1) Core JSON
        $jsonFile = $langPath.'/'.$locale.'.json';
        if (file_exists($jsonFile)) {
            $decoded = json_decode(file_get_contents($jsonFile), true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    $result[] = [
                        'locale' => $locale,
                        'group' => 'single',
                        'key' => $key,
                        'value' => $value ?? '',
                        'is_vendor' => false,
                        'vendor' => null,
                    ];
                }
            }
        }

        // 2) Core PHP
        $langFolder = $langPath.'/'.$locale;
        if (is_dir($langFolder)) {
            foreach (glob($langFolder.'/*.php') ?: [] as $file) {
                $group = basename($file, '.php');
                $groupTranslations = include $file;
                if (is_array($groupTranslations)) {
                    self::flattenTranslations($groupTranslations, $result, $locale, $group, false, null);
                }
            }
        }

        // 3) Vendor JSON + PHP
        foreach (self::discoverVendorPackages($langPath) as $vendor) {
            $vendorRoot = $langPath.'/vendor/'.$vendor;

            $vendorJson = $vendorRoot.'/'.$locale.'.json';
            if (file_exists($vendorJson)) {
                $decoded = json_decode(file_get_contents($vendorJson), true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $value) {
                        $result[] = [
                            'locale' => $locale,
                            'group' => 'single',
                            'key' => $key,
                            'value' => $value ?? '',
                            'is_vendor' => true,
                            'vendor' => $vendor,
                        ];
                    }
                }
            }

            $vendorLangFolder = $vendorRoot.'/'.$locale;
            if (is_dir($vendorLangFolder)) {
                foreach (glob($vendorLangFolder.'/*.php') ?: [] as $file) {
                    $group = basename($file, '.php');
                    $groupTranslations = include $file;
                    if (is_array($groupTranslations)) {
                        self::flattenTranslations($groupTranslations, $result, $locale, $group, true, $vendor);
                    }
                }
            }
        }

        return $result;
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
        $localeInfo = Locales::info($locale);

        Language::updateOrCreate(
            [
                'code' => $localeInfo->code,
                'regional' => $localeInfo->regional,
            ],
            [
                'type' => $localeInfo->type,
                'name' => $localeInfo->locale->name,
                'native' => $localeInfo->native,
                'direction' => $localeInfo->direction->value,
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
     * @param  array{locale: string, group: string, key: string, value: string, is_vendor: bool, vendor: string|null}  $translation
     */
    private static function writeTranslation(array $translation): void
    {
        $existing = self::where('group', $translation['group'])
            ->where('key', $translation['key'])
            ->where('is_vendor', $translation['is_vendor'])
            ->where('vendor', $translation['vendor'])
            ->first();

        $stringType = LinguaType::text;

        if ($translation['locale'] === linguaDefaultLocale()) {
            $string = Str::of($translation['value'])->trim();
            if (preg_match('#(?<=<)\w+(?=[^<]*?>)#', $string->toString())) {
                $stringType = LinguaType::html;
            }
            if ($string->markdown()->toString() === $string->toString()) {
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
    }

    protected static function flattenTranslations(
        array $array,
        array &$result,
        string $locale,
        string $group,
        bool $isVendor,
        ?string $vendor,
        string $prefix = ''
    ): void {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                self::flattenTranslations($value, $result, $locale, $group, $isVendor, $vendor, $fullKey);
            } else {
                $result[] = [
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $fullKey,
                    'value' => $value ?? '',
                    'is_vendor' => $isVendor,
                    'vendor' => $vendor,
                ];
            }
        }
    }

    protected static function discoverLocales(string $langPath): array
    {
        $locales = [];

        foreach (glob($langPath.'/*.json') ?: [] as $file) {
            $locales[] = basename($file, '.json');
        }

        foreach (glob($langPath.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            if ($name !== 'vendor') {
                $locales[] = $name;
            }
        }

        foreach (self::discoverVendorPackages($langPath) as $vendor) {
            $vendorRoot = $langPath.'/vendor/'.$vendor;
            foreach (glob($vendorRoot.'/*.json') ?: [] as $file) {
                $locales[] = basename($file, '.json');
            }
            foreach (glob($vendorRoot.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $locales[] = basename($dir);
            }
        }

        return array_values(array_unique($locales));
    }

    protected static function discoverVendorPackages(string $langPath): array
    {
        $vendorDir = $langPath.'/vendor';
        if (! is_dir($vendorDir)) {
            return [];
        }

        return array_map('basename', glob($vendorDir.'/*', GLOB_ONLYDIR) ?: []);
    }

    /**
     * Count all translations for a specific locale
     */
    public static function countByLocale(string $locale): int
    {
        return self::whereRaw('(text->>?) IS NOT NULL', [$locale])->count() ?? 0;
    }

    /**
     * Get translation statistics for a specific locale
     */
    public static function getLocaleStats(string $locale): array
    {
        $total = self::count();
        $translated = self::countByLocale($locale);

        return [
            'total' => $total,
            'translated' => $translated,
            'missing' => $total - $translated,
            'percentage' => $total > 0 ? round(($translated / $total) * 100, 2) : 0,
        ];
    }
}
