<?php

/**
 * Class Lingua
 *
 * Provides a set of methods for managing and interacting with application locales, languages,
 * translations, and their respective configurations.
 */

namespace Rivalex\Lingua;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use LaravelLang\Locales\Data\LocaleData;
use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

class Lingua
{
    /**
     * Get the current application locale.
     *
     * Retrieves the currently active locale code from the Laravel application instance.
     * This method wraps the Laravel app()->getLocale() functionality.
     *
     * @return string The current locale code (e.g., 'en', 'fr', 'de')
     *
     * @example
     * $currentLocale = Lingua::getLocale();
     * // Returns: 'en'
     */
    public static function getLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Get a language query builder for the specified locale.
     *
     * Creates an Eloquent query builder to fetch a Language model by its code or regional code.
     * The locale parameter is normalized by removing extra whitespace, trimming, and converting to lowercase.
     *
     * @param string $locale The locale code to search for (e.g., 'en', 'en-US', 'fr-CA')
     *
     * @return \Illuminate\Database\Eloquent\Builder|null Query builder instance or null
     *
     * @example
     * $languageQuery = Lingua::language('en-US');
     * $language = $languageQuery->first();
     */
    protected static function language(string $locale)
    {
        $locale = Str::of($locale)->squish()->trim()->lower();
        return Language::query()->where('code', '=', $locale)
                       ->orWhere('regional', '=', $locale);
    }

    /**
     * Get a translation query builder for the specified key.
     *
     * Creates and executes a query to fetch a Translation model by its key.
     * The key parameter is normalized by removing extra whitespace and trimming.
     * This is a protected helper method used internally by other translation methods.
     *
     * @param string|null $key The translation key to search for (e.g., 'welcome.message', 'errors.404')
     *
     * @return Translation|null Translation model instance or null if not found
     *
     * @example
     * // This is a protected method used internally
     * $translation = self::translation('welcome.message');
     * // Returns: Translation model instance or null
     */
    protected static function translation(?string $key = null): Translation|null
    {
        return Translation::where('key', Str::of($key)->squish()->trim())->first();
    }

    /**
     * Optimize and clear cached files for the application.
     *
     * Executes the 'optimize:clear' artisan command to clear and rebuild
     * various caches, ensuring the application is optimized for the latest changes.
     *
     * @return void
     */
    public static function optimize(): void
    {
        Artisan::call('optimize:clear');
    }

    /**
     * Update the application's language files.
     *
     * Executes the 'lang:update' Artisan command to refresh or modify
     * the application's language files as needed.
     *
     * @return void
     */
    public static function updateLanguages(): void
    {
        Artisan::call('lang:update');
    }

    /**
     * Add a new language to the application (installs language files).
     *
     * Executes the 'lang:add' Artisan command to install language files
     * for the specified locale via Laravel Lang.
     *
     * @param string $locale The locale code to add (e.g., 'it', 'fr', 'de')
     *
     * @return void
     *
     * @example
     * Lingua::addLanguage('fr');
     * // Installs French language files via laravel-lang
     */
    public static function addLanguage(string $locale): void
    {
        Artisan::call('lang:add ' . $locale);
    }

    /**
     * Remove a language from the application (removes language files).
     *
     * Executes the 'lang:rm' Artisan command with the --force flag to remove
     * the language files for the specified locale without prompting for confirmation.
     *
     * @param string $locale The locale code to remove (e.g., 'it', 'fr', 'de')
     *
     * @return void
     *
     * @example
     * Lingua::removeLanguage('fr');
     * // Removes French language files via laravel-lang
     */
    public static function removeLanguage(string $locale): void
    {
        Artisan::call('lang:rm ' . strtolower($locale) . ' --force');
    }

    /**
     * Check if the specified locale is the default locale.
     *
     * Determines whether the given locale (or current locale if not specified) is marked
     * as the default language in the system.
     *
     * @param string|null $locale The locale code to check. If null, uses the current application locale
     *
     * @return bool True if the locale is the default locale, false otherwise
     *
     * @example
     * if (Lingua::isDefaultLocale('en')) {
     *     // 'en' is the default locale
     * }
     *
     * @example
     * if (Lingua::isDefaultLocale()) {
     *     // Current locale is the default locale
     * }
     */
    public static function isDefaultLocale(?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();
        return self::language($locale)->first()?->is_default ?? false;
    }

    /**
     * Get the display name of the specified locale.
     *
     * Retrieves the human-readable name of the language for the given locale code
     * (or current locale if not specified). Returns an empty string if not found.
     *
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return string The language display name (e.g., 'English', 'French') or empty string
     *
     * @example
     * $name = Lingua::getLocaleName('en');
     * // Returns: 'English'
     *
     * @example
     * $currentName = Lingua::getLocaleName();
     * // Returns the name of the current locale
     */
    public static function getLocaleName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return self::language($locale)->first()->name ?? '';
    }

    /**
     * Get the native name of the specified locale.
     *
     * Retrieves the native language name (in its own language) for the given locale code
     * (or current locale if not specified). Returns an empty string if not found.
     *
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return string The native language name (e.g., 'English', 'Français', 'Deutsch') or empty string
     *
     * @example
     * $native = Lingua::getLocaleNative('fr');
     * // Returns: 'Français'
     *
     * @example
     * $currentNative = Lingua::getLocaleNative();
     * // Returns the native name of the current locale
     */
    public static function getLocaleNative(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return self::language($locale)->first()->native ?? '';
    }

    /**
     * Get the text direction for the specified locale.
     *
     * Retrieves the writing direction (left-to-right or right-to-left) for the given locale code
     * (or current locale if not specified). Defaults to 'ltr' if not found.
     *
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return string The text direction: 'ltr' (left-to-right) or 'rtl' (right-to-left)
     *
     * @example
     * $direction = Lingua::getDirection('ar');
     * // Returns: 'rtl'
     *
     * @example
     * $direction = Lingua::getDirection('en');
     * // Returns: 'ltr'
     */
    public static function getDirection(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return self::language($locale)->first()->direction ?? 'ltr';
    }

    /**
     * Synchronize translations from local filesystem to database.
     *
     * Reads all translation files from the local filesystem (lang directory) and
     * syncs them to the database, updating or creating translation records as needed.
     *
     * @return void
     *
     * @example
     * Lingua::syncToDatabase();
     * // All local translation files are now synced to the database
     */
    public static function syncToDatabase(): void
    {
        Translation::syncToDatabase();
    }

    /**
     * Synchronize translations from database to local filesystem.
     *
     * Exports all translation records from the database and writes them to
     * translation files in the local filesystem (lang directory).
     *
     * @return void
     *
     * @example
     * Lingua::syncToLocal();
     * // All database translations are now synced to local files
     */
    public static function syncToLocal(): void
    {
        Translation::syncToLocal();
    }

    /**
     * Get all installed languages.
     *
     * Retrieves a collection of all Language models that are currently installed
     * in the system.
     *
     * @return \Illuminate\Database\Eloquent\Collection|array Collection of Language models
     *
     * @example
     * $languages = Lingua::languages();
     * foreach ($languages as $language) {
     *     echo $language->name;
     * }
     */
    public static function languages(): Collection|array
    {
        return Language::all();
    }

    /**
     * Get all available locale codes.
     *
     * Retrieves an array of all locale codes that are available in the system,
     * regardless of whether they are installed or not.
     *
     * @return array Array of available locale codes (e.g., ['en', 'fr', 'de', 'es', ...])
     *
     * @example
     * $available = Lingua::available();
     * // Returns: ['en', 'fr', 'de', 'es', 'it', ...]
     */
    public static function available(): array
    {
        return Locales::raw()->available();
    }

    /**
     * Get all installed locale codes.
     *
     * Retrieves an array of locale codes for all languages that are currently
     * installed in the system.
     *
     * @return array Array of installed locale codes (e.g., ['en', 'fr', 'de'])
     *
     * @example
     * $installed = Lingua::installed();
     * // Returns: ['en', 'fr']
     */
    public static function installed(): array
    {
        return Language::all()->pluck('code')->toArray();
    }

    /**
     * Get all locale codes that are available but not installed.
     *
     * Compares available locales with installed locales and returns the difference,
     * sorted alphabetically. These are locales that can be installed.
     *
     * @return array Array of non-installed locale codes (e.g., ['de', 'es', 'it'])
     *
     * @example
     * $notInstalled = Lingua::notInstalled();
     * // Returns: ['de', 'es', 'it', ...]
     */
    public static function notInstalled(): array
    {
        $installed = self::installed();
        $lang_available = Locales::raw()->available();
        $available = array_diff($lang_available, $installed);
        asort($available);

        return array_values($available);
    }

    /**
     * Check if a locale is available but not installed.
     *
     * Determines whether the specified locale code exists in the list of available
     * locales that are not yet installed in the system.
     *
     * @param string|null $locale The locale code to check
     *
     * @return bool True if the locale is available but not installed, false otherwise
     *
     * @example
     * if (Lingua::isAvailable('de')) {
     *     // German is available and can be installed
     * }
     */
    public static function isAvailable(?string $locale = null): bool
    {
        return !empty($locale) && in_array($locale, self::notInstalled());
    }

    /**
     * Check if a locale is installed.
     *
     * Determines whether the specified locale code exists in the list of
     * installed languages in the system.
     *
     * @param string|null $locale The locale code to check
     *
     * @return bool True if the locale is installed, false otherwise
     *
     * @example
     * if (Lingua::isInstalled('fr')) {
     *     // French is installed
     * }
     */
    public static function isInstalled(?string $locale = null): bool
    {
        return !empty($locale) && in_array($locale, self::installed());
    }

    /**
     * Get a Language model by locale code.
     *
     * Retrieves the Language model for the specified locale code.
     * Returns null if the language is not found.
     *
     * @param string|null $locale The locale code to retrieve
     *
     * @return \Rivalex\Lingua\Models\Language|null Language model instance or null
     *
     * @example
     * $language = Lingua::get('en');
     * if ($language) {
     *     echo $language->name;
     * }
     */
    public static function get(?string $locale = null): ?Language
    {
        return Language::where('code', $locale)->first();
    }

    /**
     * Get the default Language model.
     *
     * Retrieves the Language model that is marked as the default language
     * in the system. Returns null if no default language is set.
     *
     * @return \Rivalex\Lingua\Models\Language|null Default Language model instance or null
     *
     * @example
     * $defaultLanguage = Lingua::getDefault();
     * echo $defaultLanguage->code; // e.g., 'en'
     */
    public static function getDefault(): ?Language
    {
        return Language::where('is_default', true)->first();
    }

    /**
     * Get detailed information about a locale.
     *
     * Retrieves comprehensive LocaleData information for the specified locale,
     * optionally including country and currency information.
     *
     * @param mixed $locale       The locale code to get information for
     * @param bool  $withCountry  Whether to include country information (default: false)
     * @param bool  $withCurrency Whether to include currency information (default: false)
     *
     * @return \LaravelLang\Locales\Data\LocaleData LocaleData object containing locale information
     *
     * @example
     * $info = Lingua::info('en-US', withCountry: true, withCurrency: true);
     * echo $info->native; // 'English (United States)'
     */
    public static function info(mixed $locale, bool $withCountry = false, bool $withCurrency = false): LocaleData
    {
        return Locales::info(locale: $locale, withCountry: $withCountry, withCurrency: $withCurrency);
    }

    /**
     * Get the fallback Language model.
     *
     * Retrieves the Language model for the application's fallback locale.
     * The fallback locale is used when a translation is not available in the current locale.
     * Returns null if the fallback language is not found.
     *
     * @return \Rivalex\Lingua\Models\Language|null Fallback Language model instance or null
     *
     * @example
     * $fallback = Lingua::getFallback();
     * echo $fallback->code; // e.g., 'en'
     */
    public static function getFallback(): ?Language
    {
        return Language::where('code', app()->getFallbackLocale())->first();
    }

    /**
     * Get all translations.
     *
     * Retrieves a collection of all Translation models stored in the database,
     * regardless of locale or group.
     *
     * @return \Illuminate\Database\Eloquent\Collection|array Collection of Translation models
     *
     * @example
     * $translations = Lingua::translations();
     * foreach ($translations as $translation) {
     *     echo $translation->key;
     * }
     */
    public static function translations(): Collection|array
    {
        return Translation::all();
    }

    /**
     * Get all translations for a specific key across all locales.
     *
     * Retrieves an associative array of all translations for the given key,
     * where keys are locale codes and values are translated strings.
     * Returns null if the translation key is not found.
     *
     * @param string|null $key The translation key (e.g., 'welcome.message')
     *
     * @return array Associative array of translations (e.g., ['en' => 'Hello', 'fr' => 'Bonjour']) or empty array
     *
     * @example
     * $translations = Lingua::getTranslations('welcome.message');
     * // Returns: ['en' => 'Welcome', 'fr' => 'Bienvenue', 'de' => 'Willkommen']
     */
    public static function getTranslations(?string $key): array
    {
        return self::translation($key)?->text ?? [];
    }

    /**
     * Get a translation for a specific key and locale.
     *
     * Retrieves the translated string for the given key in the specified locale
     * (or current locale if not specified). Returns an empty string if not found.
     *
     * @param string|null $key    The translation key (e.g., 'welcome.message')
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return string The translated string or empty string if not found
     *
     * @example
     * $welcome = Lingua::getTranslation('welcome.message', 'fr');
     * // Returns: 'Bienvenue'
     *
     * @example
     * $currentWelcome = Lingua::getTranslation('welcome.message');
     * // Returns translation in current locale
     */
    public static function getTranslation(?string $key, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return self::translation($key)?->text[$locale] ?? '';
    }

    /**
     * Set a translation for a specific key and locale.
     *
     * Updates or creates a translation for the given key in the specified locale
     * (or current locale if not specified). The translation is saved to the database.
     *
     * @param string|null $key    The translation key (e.g., 'welcome.message')
     * @param string      $value  The translated string value
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return void
     *
     * @example
     * Lingua::setTranslation('welcome.message', 'Bienvenue', 'fr');
     * // Sets French translation for 'welcome.message'
     *
     * @example
     * Lingua::setTranslation('welcome.message', 'Welcome');
     * // Sets translation for current locale
     */
    public static function setTranslation(?string $key, string $value, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        $translation = self::translation($key);
        if(!$translation) {
            return;
        }
        $translation->setTranslation($locale, $value);
        $translation->save();
    }

    /**
     * Remove a translation for a specific key and locale.
     *
     * Deletes the translation for the given key in the specified locale
     * (or current locale if not specified). If the locale is the default locale,
     * the entire translation record is deleted. Vendor translations cannot be deleted.
     *
     * @param string|null $key    The translation key (e.g., 'welcome.message')
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return void
     *
     * @throws VendorTranslationProtectedException If attempting to delete a vendor translation
     *
     * @example
     * Lingua::forgetTranslation('welcome.message', 'fr');
     * // Removes French translation for 'welcome.message'
     *
     * @example
     * Lingua::forgetTranslation('welcome.message');
     * // Removes translation for current locale
     */
    public static function forgetTranslation(?string $key, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        $translation = self::translation($key);
        if(!$translation) {
            return;
        }
        if ($translation->is_vendor) {
            throw new VendorTranslationProtectedException;
        }
        if (self::isDefaultLocale($locale)) {
            $translation->delete();
        } else {
            $translation->forgetTranslation(locale: $locale);
        }
    }

    /**
     * Get translations by group.
     *
     * Retrieves a collection of Translation models that belong to the specified group.
     * Optionally filters to only include translations that have a value for the specified locale.
     *
     * @param string      $group  The translation group name (e.g., 'messages', 'validation')
     * @param string|null $locale Optional locale code to filter translations
     *
     * @return \Illuminate\Database\Eloquent\Collection|array Collection of Translation models
     *
     * @example
     * $messages = Lingua::getTranslationByGroup('messages');
     * // Returns all translations in the 'messages' group
     *
     * @example
     * $frenchMessages = Lingua::getTranslationByGroup('messages', 'fr');
     * // Returns translations in 'messages' group that have French translations
     */
    public static function getTranslationByGroup(string $group, ?string $locale = null): Collection|array
    {
        return Translation::where('group', Str::of($group)->squish()->trim())
                          ->when($locale, fn($query) => $query->whereNotNull('text->' . $locale))
                          ->get();
    }

    /**
     * Get the default locale code.
     *
     * Retrieves the locale code of the default language. Falls back to the
     * configuration value 'lingua.default_locale' or 'en' if no default is set.
     *
     * @return string The default locale code (e.g., 'en', 'fr')
     *
     * @example
     * $default = Lingua::getDefaultLocale();
     * // Returns: 'en'
     */
    public static function getDefaultLocale(): string
    {
        return Language::default()?->code ?? config('lingua.default_locale', 'en');
    }

    /**
     * Check if a locale exists in the system.
     *
     * Determines whether a Language record exists for the specified locale code
     * (by either code or regional code).
     *
     * @param string $locale The locale code to check
     *
     * @return bool True if the locale exists, false otherwise
     *
     * @example
     * if (Lingua::hasLocale('en-US')) {
     *     // Language exists
     * }
     */
    public static function hasLocale(string $locale): bool
    {
        return self::language($locale)->exists();
    }

    /**
     * Set the default locale for the application.
     *
     * Marks the specified locale as the default language in the system.
     * All other languages will have their is_default flag set to false.
     *
     * @param string $locale The locale code to set as default
     *
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the locale is not found
     *
     * @example
     * Lingua::setDefaultLocale('fr');
     * // French is now the default language
     */
    public static function setDefaultLocale(string $locale): void
    {
        $language = self::language($locale)->firstOrFail();
        Language::setDefault($language);
    }

    /**
     * Get translation statistics for a locale.
     *
     * Retrieves statistical information about translations for the specified locale
     * (or current locale if not specified), such as total translations, completed,
     * and missing translations.
     *
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return array Associative array containing translation statistics
     *
     * @example
     * $stats = Lingua::getLocaleStats('fr');
     * // Returns: ['total' => 100, 'translated' => 85, 'missing' => 15, ...]
     *
     * @example
     * $currentStats = Lingua::getLocaleStats();
     * // Returns statistics for current locale
     */
    public static function getLocaleStats(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        return Translation::getLocaleStats($locale);
    }

    /**
     * Get all languages with translation statistics.
     *
     * Retrieves a collection of all Language models enriched with translation
     * statistics (e.g., total translations, completion percentage, etc.).
     *
     * @return \Illuminate\Database\Eloquent\Collection|array Collection of Language models with statistics
     *
     * @example
     * $languages = Lingua::languagesWithStatistics();
     * foreach ($languages as $language) {
     *     echo "{$language->name}: {$language->completion_percentage}%";
     * }
     */
    public static function languagesWithStatistics(): Collection|array
    {
        return Language::withStatistics()->get();
    }

    /**
     * Get vendor translations.
     *
     * Retrieves a collection of Translation models for a specific vendor package.
     * Optionally filters to only include translations that have a value for the specified locale.
     *
     * @param string      $vendor The vendor package name (e.g., 'laravel', 'spatie')
     * @param string|null $locale Optional locale code to filter translations
     *
     * @return \Illuminate\Database\Eloquent\Collection|array Collection of Translation models
     *
     * @example
     * $laravelTranslations = Lingua::getVendorTranslations('laravel');
     * // Returns all Laravel vendor translations
     *
     * @example
     * $laravelFrench = Lingua::getVendorTranslations('laravel', 'fr');
     * // Returns Laravel vendor translations that have French translations
     */
    public static function getVendorTranslations(string $vendor, ?string $locale = null): Collection|array
    {
        return Translation::where('is_vendor', true)
                          ->where('vendor', Str::of($vendor)->squish()->trim())
                          ->when($locale, fn($q) => $q->whereNotNull('text->' . $locale))
                          ->get();
    }

    /**
     * Set a vendor translation.
     *
     * Updates a translation for a specific vendor package, group, and key in the specified locale
     * (or current locale if not specified). The translation is saved to the database.
     *
     * @param string      $vendor The vendor package name (e.g., 'laravel', 'spatie')
     * @param string      $group  The translation group name (e.g., 'validation', 'passwords')
     * @param string      $key    The translation key (e.g., 'required', 'min.string')
     * @param string      $value  The translated string value
     * @param string|null $locale The locale code. If null, uses the current application locale
     *
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the vendor translation is not found
     *
     * @example
     * Lingua::setVendorTranslation('laravel', 'validation', 'required', 'Ce champ est requis', 'fr');
     * // Sets French translation for Laravel's validation.required message
     *
     * @example
     * Lingua::setVendorTranslation('laravel', 'passwords', 'reset', 'Password has been reset');
     * // Sets translation for current locale
     */
    public static function setVendorTranslation(string  $vendor, string $group, string $key, string $value,
                                                ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        $translation = Translation::where('is_vendor', true)
                                  ->where('vendor', Str::of($vendor)->squish()->trim())
                                  ->where('group', Str::of($group)->squish()->trim())
                                  ->where('key', Str::of($key)->squish()->trim())
                                  ->firstOrFail();
        $translation->setTranslation($locale, $value);
        $translation->save();
    }

}
