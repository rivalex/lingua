<?php

namespace Rivalex\Lingua\Models;

use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * # Language Model
 * Represents a language configuration in the system with translation statistics tracking.
 * This model manages language settings, default language selection, sorting order, and
 * provides translation completion statistics.
 *
 *  ## Create a new language.
 *  ```
 *  // Create a new language
 *  $language = Language::create([
 *      'code' => 'es',
 *      'regional' => 'ES',
 *      'type' => 'standard',
 *      'name' => 'Spanish',
 *      'native' => 'Español',
 *      'direction' => 'ltr',
 *      'is_default' => false
 *  ]);
 *  ```
 *
 *  ## Get default language:
 *  ```
 *  $defaultLang = Language::default();
 *  echo $defaultLang->name; // 'English'
 *  ```
 *
 * ## Set a new default language:
 *  ```
 *  $spanish = Language::where('code', 'es')->first();
 *  Language::setDefault($spanish);
 *  ```
 *
 * ## Get languages with translation statistics:
 *  ```
 *  $languages = Language::withStatistics()->get();
 *  foreach ($languages as $lang) {
 *      echo "{$lang->name}: {$lang->completion_percentage}% complete\n";
 *      echo "Translated: {$lang->translated_strings}/{$lang->total_strings}\n";
 *  }
 *  ```
 *
 * ## Reorder languages sequentially:
 *  ```
 *  Language::reorderLanguages();
 *  ```
 *
 * ## Get active languages ordered by sort:
 *  ```
 *  $languages = Language::query->active()->get();
 *  ```
 *
 *
 * @property int        $id                    ID primary key
 * @property string     $code                  ISO 639-1 language code (e.g., 'en', 'es', 'fr')
 * @property string     $regional              Regional variant code (e.g., 'US', 'GB', 'MX')
 * @property string     $type                  Language type classification
 * @property string     $name                  English name of the language (e.g., 'English', 'Spanish')
 * @property string     $native                Native name of the language (e.g., 'English', 'Español')
 * @property string     $direction             Text direction: 'ltr' (left-to-right) or 'rtl' (right-to-left)
 * @property bool       $is_default            Indicates if this is the default system language
 * @property int        $sort                  Display order position (lower numbers appear first)
 * @property Carbon     $created_at            Creation timestamp
 * @property Carbon     $updated_at            Last update timestamp
 *
 * @property-read int   $total_strings         Total number of translatable strings (computed)
 * @property-read int   $translated_strings    Number of translated strings for this language (computed)
 * @property-read int   $missing_strings       Number of untranslated strings (computed)
 * @property-read float $completion_percentage Translation completion percentage (0-100) (computed)
 */
class Language extends Model
{
    protected $table = 'languages';

    protected $fillable = [
        'code',
        'regional',
        'type',
        'name',
        'native',
        'direction',
        'is_default',
        'sort',
    ];

    protected $casts = [
        'code' => 'string',
        'regional' => 'string',
        'type' => 'string',
        'name' => 'string',
        'native' => 'string',
        'direction' => 'string',
        'is_default' => 'boolean',
        'sort' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_strings' => 'integer',
        'translated_strings' => 'integer',
        'missing_strings' => 'integer',
        'completion_percentage' => 'float',
    ];

    /**
     * ## Boot the model and register event listeners.
     *
     * This method automatically generates a UUID for new language records and sets
     * the sort order to the next available position (max + 1) to ensure new languages
     * are added at the end of the list.
     * When creating a new language, ID and sort are auto-generated:
     *  ```
     *  $lang = new Language(['code' => 'fr', 'name' => 'French']);
     *  $lang->save();
     *
     *  $lang->id // is now a UUID string.
     *  $lang->sort // is automatically set to the highest sort value + 1
     *  ```
     */
    public static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->sort = self::max('sort') + 1;
        });
    }

    /**
     * ## Query scope for active languages ordered by sort position.
     *
     * Returns a query builder instance that orders languages by their sort field
     * in ascending order. This method is useful for displaying languages in the
     * configured display order.
     *
     *   ```
     *   // Get all languages in sorted order
     *   $languages = Language::query()->active()->get();
     *
     *   // Apply additional filters
     *   $ltrLanguages = Language::query()->active()->where('direction', 'ltr')->get();
     *   ```
     *
     * @param Builder $query Query builder instance
     *
     * @return Builder Modified query builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->orderBy('sort');
    }

    /**
     * ## Generate database-specific SQL expression to check if a JSON key exists.
     *
     * This method creates a raw SQL expression that checks whether a specific key exists
     * in a JSON column. The expression varies based on the database driver (PostgreSQL,
     * SQL Server, SQLite, or MySQL/MariaDB) to ensure compatibility across different
     * database systems.
     *
     * The method is primarily used internally by the `withStatistics()` scope to determine
     * which translations exist for a specific language code in the JSONB 'text' column
     * of the language_lines table.
     *
     * **Supported Databases:**
     * - **PostgreSQL**: Uses the `->>` operator to extract and check for NULL
     * - **SQL Server**: Uses `JSON_VALUE()` function with dynamic path construction
     * - **SQLite**: Uses `json_extract()` function with concatenated path
     * - **MySQL/MariaDB**: Uses `JSON_EXTRACT()` function with concatenated path (default)
     *
     *  ```
     *  // Internal usage in withStatistics scope
     *  $languageCodeColumn = $query->getModel()->qualifyColumn('code');
     *  $jsonColumn = 'language_lines.text';
     *  $keyExists = $this->jsonKeyExistsExpression($jsonColumn, $languageCodeColumn);
     *
     *  // The generated expression will be different based on the database:
     *  // PostgreSQL: "(language_lines.text ->> languages.code) IS NOT NULL"
     *  // MySQL: "JSON_EXTRACT(language_lines.text, CONCAT('$.\"', languages.code, '\"')) IS NOT NULL"
     *  // SQLite: "json_extract(language_lines.text, '$.' || languages.code) IS NOT NULL"
     *  // SQL Server: "JSON_VALUE(language_lines.text, CONCAT('$.\"', languages.code, '\"')) IS NOT NULL"
     *
     *  // Use in raw query
     *  $query->whereRaw($keyExists);
     *
     *  // Example: Count translations for a specific language
     *  $language = Language::find($id);
     *  $jsonColumn = 'language_lines.text';
     *  $keyColumn = "'{$language->code}'";
     *  $expression = $language->jsonKeyExistsExpression($jsonColumn, $keyColumn);
     *  $count = DB::table('language_lines')->whereRaw($expression)->count();
     *  ```
     *
     * @param string $jsonColumn The name of the JSON column to check (e.g., 'language_lines.text')
     * @param string $keyColumn  The column name or value containing the key to search for (e.g., 'languages.code' or "'en'")
     *
     * @return string Raw SQL expression string that evaluates to true if the key exists
     */
    protected function jsonKeyExistsExpression(string $jsonColumn, string $keyColumn): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "($jsonColumn ->> $keyColumn) IS NOT NULL",
            'sqlsrv' => "JSON_VALUE($jsonColumn, CONCAT('$.\"', $keyColumn, '\"')) IS NOT NULL",
            'sqlite' => "json_extract($jsonColumn, '$.' || $keyColumn) IS NOT NULL",
            default => "JSON_EXTRACT($jsonColumn, CONCAT('$.\"', $keyColumn, '\"')) IS NOT NULL",
        };
    }

    /**
     * ## Query scope to include translation statistics for each language.
     *
     * This scope adds computed columns that provide translation progress information:
     * - **total_strings**: Total count of translatable strings in the system
     * - **translated_strings**: Number of strings that have been translated for this language
     * - **missing_strings**: Number of strings that still need translation
     * - **completion_percentage**: Percentage of translated strings (0-100, rounded to 2 decimals)
     *
     * The statistics are calculated from the language_lines table where translations
     * are stored in a JSONB 'text' column with language codes as keys.
     *
     *  ```
     *  // Get language with translation statistics
     *  $language = Language::query()->withStatistics()->find($id);
     *  echo "Translation progress: {$language->completion_percentage}%";
     *  echo "Missing: {$language->missing_strings} strings";
     *
     *  // Get all languages with statistics
     *  $languages = Language::query()->withStatistics()->get();
     *  foreach ($languages as $lang) {
     *      if ($lang->completion_percentage < 80) {
     *          echo "{$lang->name} needs more translations\n";
     *      }
     *  }
     *
     *  // Combine with other queries
     *  $incompleteLangs = Language::withStatistics()
     *      ->havingRaw('completion_percentage < 100')
     *      ->orderBy('completion_percentage', 'desc')
     *      ->get();
     *  ```
     *
     * @param Builder $query Query builder instance
     *
     * @return Builder Modified query builder with statistics
     */
    public function scopeWithStatistics(Builder $query): Builder
    {
        $languageCodeColumn = $query->getModel()->qualifyColumn('code');
        $jsonColumn = 'language_lines.text';

        $keyExists = $this->jsonKeyExistsExpression($jsonColumn, $languageCodeColumn);

        $total = DB::table('language_lines')->selectRaw('COUNT(*)');
        $translated = DB::table('language_lines')->whereRaw($keyExists)->selectRaw('COUNT(*)');
        $missing = DB::table('language_lines')->whereRaw("NOT ($keyExists)")->selectRaw('COUNT(*)');
        $percentage = DB::table('language_lines')->selectRaw('ROUND(COUNT(CASE WHEN ' . $keyExists . ' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2)');

        return $query->addSelect([
            'total_strings' => $total,
            'translated_strings' => $translated,
            'missing_strings' => $missing,
            'completion_percentage' => $percentage
        ]);
    }

    /**
     * ## Get the default system language.
     *
     * Retrieves the language that is marked as the default (is_default = true).
     * Only one language should be set as default at any time. Returns null if
     * no default language is configured.
     *
     *  ```
     *  // Get the default language
     *  $defaultLang = Language::default();
     *  if ($defaultLang) {
     *      echo "Default language: {$defaultLang->name}";
     *      app()->setLocale($defaultLang->code);
     *  }
     *
     *  // Use in middleware or service providers
     *  $locale = Language::default()->code ?? 'en';
     *  ```
     *
     * @return self|null The default language instance or null if not found
     */
    public static function default(): ?self
    {
        return self::where('is_default', true)->first();
    }

    /**
     * ## Reorder all languages with sequential sort values.
     *
     * This method fetches all languages ordered by their current sort value and
     * reassigns sequential sort numbers starting from 1. This is useful for
     * fixing gaps in sort order that may occur after deletions or manual sorting.
     *
     *  ```
     *  // After deleting languages or manual reordering, fix the sort sequence
     *  Language::reorderLanguages();
     *  // Now languages will have sort values: 1, 2, 3, 4... with no gaps
     *
     *  // Use after bulk operations
     *  Language::whereIn('code', ['xx', 'yy'])->delete();
     *  Language::reorderLanguages(); // Clean up sort order
     *  ```
     */
    public static function reorderLanguages(): void
    {
        self::orderBy('sort')
            ->get()
            ->each(function ($language, $index) {
                $language->update(['sort' => $index + 1]);
            });
    }

    /**
     * Set a language as the default system language.
     *
     * This method unsets any existing default language and sets the provided
     * language as the new default. It ensures that only one language is marked
     * as default at any time by first setting all languages' is_default to false,
     * then setting the specified language's is_default to true.
     *
     *  ```
     *  // Set Spanish as the default language
     *  $spanish = Language::where('code', 'es')->first();
     *  Language::setDefault($spanish);
     *
     *  // Verify the change
     *  $default = Language::default();
     *  echo $default->code; // 'es'
     *
     *  // Use in language switcher
     *  $newDefault = Language::find($request->language_id);
     *  Language::setDefault($newDefault);
     *  session()->put('locale', $newDefault->code);
     *  ```
     *
     * @param self $language The language instance to set as default
     */
    public static function setDefault(self $language): void
    {
        self::where('is_default', true)->update(['is_default' => false]);
        $language->update(['is_default' => true]);
    }

}
