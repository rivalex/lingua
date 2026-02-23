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
 * @property string     $id UUID identifier for the translation
 * @property string     $group Translation group name (e.g., 'single', 'validation', etc.)
 * @property string     $key Translation key within the group
 * @property string     $group_key Translation group and key concatenated.
 * @property LinguaType $type Type of translation (text, html, etc.)
 * @property array      $text Associative array of translations (locale => translation)
 * @property Carbon     $created_at Creation timestamp
 * @property Carbon     $updated_at Last update timestamp
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
    ];

    /**
     * Bootstrap the model and its traits.
     * Automatically generates UUID for new translations.
     */
    public static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->group_key = Str::wrap('.', before: Str::squish($model->group), after: Str::squish($model->key));
        });
        static::saving(function ($model) {
            if ($model->isDirty('group') || $model->isDirty('key')) {
                $model->group_key = Str::wrap('.', before: Str::squish($model->group), after: Str::squish($model->key));
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
            $groups = [];

            foreach ($translations as $translation) {
                $text = $translation->text;
                if (isset($text[$locale])) {
                    if ($translation->group === 'single') {
                        $groups['single'][$translation->key] = $text[$locale];
                    } else {
                        // Use data_set to expand dot notation keys back into nested arrays
                        data_set($groups[$translation->group], $translation->key, $text[$locale]);
                    }
                }
            }

            // Sync JSON single file (e.g., lang/en.json)
            if (isset($groups['single'])) {
                $jsonFile = $langPath.'/'.$locale.'.json';
                file_put_contents($jsonFile,
                    json_encode($groups['single'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            // Sync PHP files (e.g., lang/en/*.php)
            foreach ($groups as $group => $groupTranslations) {
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

        Artisan::call('lang:add ' . config('lingua.default_locale'));

        foreach (Locales::installed() as $installedLocale) {
            $locale = $installedLocale->code;
            // 1. Sync JSON single file (e.g., lang/en.json)
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
                        ];
                    }
                }
            }

            // 2. Sync PHP files from language folder (e.g., lang/en/*.php)
            $langFolder = $langPath.'/'.$locale;
            if (is_dir($langFolder)) {
                $phpFiles = glob($langFolder.'/*.php');

                foreach ($phpFiles as $file) {
                    $group = basename($file, '.php');
                    $groupTranslations = include $file;

                    if (is_array($groupTranslations)) {
                        self::flattenTranslations($groupTranslations, $translations, $locale, $group);
                    }
                }
            }
        }

        foreach ($translations as $translation) {
            try {
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
                    ->first();

                Translation::updateOrCreate([
                    'group' => $translation['group'],
                    'key' => $translation['key'],
                ], [
                    'type' => $existing->type ?? $stringType,
                    'text' => array_merge(
                        $existing->text ?? [],
                        [$translation['locale'] => $translation['value']]),
                ]);
            } catch (\Throwable $th) {
                throw $th;
            }
        }
    }

    /**
     * Flatten nested translation arrays into dot notation.
     *
     * @param  array  $array  Source translations array
     * @param  array  &$result  Reference to result array
     * @param  string  $locale  Current locale
     * @param  string  $group  Translation group
     * @param  string  $prefix  Current key prefix
     */
    protected static function flattenTranslations(array $array, array &$result, string $locale, string $group,
        string $prefix = ''): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                self::flattenTranslations($value, $result, $locale, $group, $fullKey);
            } else {
                $result[] = [
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $fullKey,
                    'value' => $value ?? '',
                ];
            }
        }
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
