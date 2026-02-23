<?php

namespace Rivalex\Lingua\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use LaravelLang\Locales\Facades\Locales;
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
 */
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
     * Bootstrap the model and its traits.
     * Automatically generates UUID for new translations.
     */
    public static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            //            $model->group_key = Str::wrap('.', before: Str::squish($model->group), after: Str::squish($model->key));
            $model->group_key = self::buildGroupKey(
                $model->group,
                $model->key,
                $model->is_vendor ?? false,
                $model->vendor
            );
        });
        static::saving(function ($model) {
            //            if ($model->isDirty('group') || $model->isDirty('key')) {
            //                $model->group_key = Str::wrap('.', before: Str::squish($model->group), after: Str::squish($model->key));
            //            }
            if ($model->isDirty('group') || $model->isDirty('key') || $model->isDirty('is_vendor') || $model->isDirty('vendor')) {
                $model->group_key = self::buildGroupKey(
                    $model->group,
                    $model->key,
                    $model->is_vendor ?? false,
                    $model->vendor
                );
            }
        });
        static::saved(function () {
            Artisan::call('cache:clear');
        });
    }

    protected function groupKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Str::squish($value),
            set: fn () => Str::wrap('.', before: Str::squish($this->group), after: Str::squish($this->key))
        );
    }

    protected static function buildGroupKey(string $group, string $key, bool $isVendor, ?string $vendor): string
    {
        $prefix = $isVendor && $vendor ? $vendor.'::' : '';

        return Str::wrap('.', before: $prefix.Str::squish($group), after: Str::squish($key));
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
            if (isset($coreGroups['single'])) {
                $jsonFile = $langPath.'/'.$locale.'.json';
                file_put_contents($jsonFile,
                    json_encode($coreGroups['single'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

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
     * Synchronize local translation files to database.
     * Imports translations from JSON and PHP files into the database.
     */
    public static function syncToDatabase(): void
    {
        $langPath = config('lingua.lang_dir');
        $translations = [];

        if (Locales::installed()->count() === 0) {
            Artisan::call('lang:add '.config('lingua.default_locale'));
        }

        $locales = array_values(array_unique(array_merge(
            self::discoverLocales($langPath),
            Locales::installed()->pluck('code')->all()
        )));

        foreach ($locales as $locale) {
            // 1) Core JSON
            $jsonFile = $langPath.'/'.$locale.'.json';
            if (file_exists($jsonFile)) {
                $jsonTranslations = json_decode(file_get_contents($jsonFile), true);
                if (is_array($jsonTranslations)) {
                    foreach ($jsonTranslations as $key => $value) {
                        $translations[] = [
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
                $phpFiles = glob($langFolder.'/*.php') ?: [];
                foreach ($phpFiles as $file) {
                    $group = basename($file, '.php');
                    $groupTranslations = include $file;
                    if (is_array($groupTranslations)) {
                        self::flattenTranslations($groupTranslations, $translations, $locale, $group, false, null);
                    }
                }
            }

            // 3) Vendor JSON + PHP
            foreach (self::discoverVendorPackages($langPath) as $vendor) {
                $vendorRoot = $langPath.'/vendor/'.$vendor;

                $vendorJson = $vendorRoot.'/'.$locale.'.json';
                if (file_exists($vendorJson)) {
                    $jsonTranslations = json_decode(file_get_contents($vendorJson), true);
                    if (is_array($jsonTranslations)) {
                        foreach ($jsonTranslations as $key => $value) {
                            $translations[] = [
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
                    $phpFiles = glob($vendorLangFolder.'/*.php') ?: [];
                    foreach ($phpFiles as $file) {
                        $group = basename($file, '.php');
                        $groupTranslations = include $file;
                        if (is_array($groupTranslations)) {
                            self::flattenTranslations($groupTranslations, $translations, $locale, $group, true, $vendor);
                        }
                    }
                }
            }
        }

        foreach ($translations as $translation) {
            $newLanguage = Locales::info($translation['locale']);

            Language::updateOrCreate(
                [
                    'code' => $newLanguage->code,
                    'regional' => $newLanguage->regional,
                ],
                [
                    'type' => $newLanguage->type,
                    'name' => $newLanguage->locale->name,
                    'native' => $newLanguage->native,
                    'direction' => $newLanguage->direction->value,
                ]
            );

            $stringType = LinguaType::text;
            if ($translation['locale'] === defaultLocale()) {
                $string = Str::of($translation['value'])->trim();
                if (preg_match('#(?<=<)\w+(?=[^<]*?>)#', $string->toString())) {
                    $stringType = LinguaType::html;
                }
                if ($string->markdown()->toString() === $string->toString()) {
                    $stringType = LinguaType::markdown;
                }
            }

            $existing = self::where('group', $translation['group'])
                ->where('key', $translation['key'])
                ->where('is_vendor', $translation['is_vendor'])
                ->where('vendor', $translation['vendor'])
                ->first();

            Translation::updateOrCreate([
                'group' => $translation['group'],
                'key' => $translation['key'],
                'is_vendor' => $translation['is_vendor'],
                'vendor' => $translation['vendor'],
            ], [
                'type' => $existing->type ?? $stringType,
                'text' => array_merge(
                    $existing->text ?? [],
                    [$translation['locale'] => $translation['value']]
                ),
            ]);
        }
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
