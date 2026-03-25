<?php

namespace Rivalex\Lingua\Facades;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;
use LaravelLang\Locales\Data\LocaleData;
use Rivalex\Lingua\Models\Language;

/**
 * ## Lingua Facade
 *
 * Provides a static interface to the Lingua service for managing locales,
 * languages, and translations in a Laravel application backed by a database.
 *
 * ### Locale helpers
 * ```
 * $locale  = Lingua::getLocale();           // 'en'
 * $name    = Lingua::getLocaleName('fr');   // 'French'
 * $native  = Lingua::getLocaleNative('fr'); // 'Français'
 * $dir     = Lingua::getDirection('ar');    // 'rtl'
 * $default = Lingua::getDefaultLocale();   // 'en'
 * $info    = Lingua::info('en-US', withCountry: true);
 * ```
 *
 * ### Language queries
 * ```
 * $all       = Lingua::languages();          // Collection of Language models
 * $language  = Lingua::get('fr');            // Language|null
 * $default   = Lingua::getDefault();         // default Language|null
 * $fallback  = Lingua::getFallback();        // fallback Language|null
 * $withStats = Lingua::languagesWithStatistics();
 * ```
 *
 * ### Locale availability / installation checks
 * ```
 * Lingua::available();          // ['en', 'fr', 'de', ...] — all known locale codes
 * Lingua::installed();          // ['en', 'fr'] — locale codes currently in DB
 * Lingua::notInstalled();       // ['de', 'es', ...] — available but not installed
 * Lingua::isInstalled('fr');    // true / false
 * Lingua::isAvailable('de');    // true if available but not yet installed
 * Lingua::hasLocale('en-US');   // true if found by code or regional
 * Lingua::isDefaultLocale('en'); // true / false
 * ```
 *
 * ### Set default locale
 * ```
 * Lingua::setDefaultLocale('fr');
 * ```
 *
 * ### Translation helpers
 * ```
 * $all    = Lingua::translations();                     // Collection of Translation models
 * $map    = Lingua::getTranslations('welcome.message'); // ['en' => 'Welcome', 'fr' => 'Bienvenue']
 * $value  = Lingua::getTranslation('welcome.message', 'fr'); // 'Bienvenue'
 * $stats  = Lingua::getLocaleStats('fr');               // ['total'=>100,'translated'=>85,...]
 * $group  = Lingua::getTranslationByGroup('validation', 'fr');
 *
 * Lingua::setTranslation('welcome.message', 'Bienvenue', 'fr');
 * Lingua::forgetTranslation('welcome.message', 'fr'); // removes locale; deletes record if default
 * ```
 *
 * ### Vendor translation helpers
 * ```
 * $vendor = Lingua::getVendorTranslations('spatie', 'fr');
 * Lingua::setVendorTranslation('spatie', 'validation', 'required', 'Champ requis', 'fr');
 * ```
 *
 * ### Language lifecycle
 * ```
 * Lingua::addLanguage('fr');      // install language files via lang:add
 * Lingua::removeLanguage('fr');   // remove language files via lang:rm --force
 * ```
 *
 * ### Sync & maintenance
 * ```
 * Lingua::syncToDatabase();   // import local lang files → database
 * Lingua::syncToLocal();      // export database → local lang files
 * Lingua::updateLanguages();  // run lang:update (fetch latest translations)
 * Lingua::optimize();         // run optimize:clear
 * ```
 * ---
 *
 * @method static string getLocale() Get the current application locale code (e.g. 'en')
 * @method static bool isDefaultLocale(?string $locale = null) True if the locale (or current locale when null) is the default one
 * @method static string getLocaleName(?string $locale = null) English display name of the locale (e.g. 'French'); '' when not found
 * @method static string getLocaleNative(?string $locale = null) Native name of the locale (e.g. 'Français'); '' when not found
 * @method static string getDirection(?string $locale = null) Text direction for the locale — 'ltr' or 'rtl'; defaults to 'ltr'
 * @method static string getDefaultLocale() The locale code of the default language (e.g. 'en')
 * @method static LocaleData info(mixed $locale, bool $withCountry = false, bool $withCurrency = false) Detailed locale data from laravel-lang/locales
 * @method static void addLanguage(string $locale) Install language files for the given locale via lang:add
 * @method static void removeLanguage(string $locale) Remove language files for the given locale via lang:rm --force
 * @method static void optimize() Clear the application cache (calls optimize:clear)
 * @method static void updateLanguages() Run lang:update to fetch the latest translations
 * @method static void syncToDatabase() Import local lang/ files into the database
 * @method static void syncToLocal() Export database translations to local lang/ files
 * @method static Collection languages() All installed Language models
 * @method static array available() All known locale codes (installed + not installed)
 * @method static array installed() Locale codes currently installed in the database
 * @method static array notInstalled() Locale codes that are available but not yet installed, sorted alphabetically
 * @method static bool isAvailable(?string $locale = null) True if the locale is available but not installed
 * @method static bool isInstalled(?string $locale = null) True if the locale is installed
 * @method static Language|null get(?string $locale = null) Language model for the given locale code, or null
 * @method static Language|null getDefault() The Language model marked as default, or null
 * @method static Language|null getFallback() The Language model for the application fallback locale, or null
 * @method static bool hasLocale(string $locale) True if a Language record exists for the given code or regional value
 * @method static void setDefaultLocale(string $locale) Mark the given locale as the system default
 * @method static Collection languagesWithStatistics() All Language models enriched with translation statistics
 * @method static Collection translations() All Translation models
 * @method static array getTranslations(?string $key) All locale values for a translation key as ['locale' => 'value', ...]; [] when not found
 * @method static string getTranslation(?string $key, ?string $locale = null) Translated string for a key and locale; '' when not found
 * @method static void setTranslation(?string $key, string $value, ?string $locale = null) Save a translation value; does nothing when the key does not exist
 * @method static void forgetTranslation(?string $key, ?string $locale = null) Remove a locale's translation; deletes the whole record when locale is the default
 * @method static Collection getTranslationByGroup(string $group, ?string $locale = null) Translations belonging to a group, optionally filtered to those with a value for the given locale
 * @method static array getLocaleStats(?string $locale = null) Translation stats for a locale: ['total', 'translated', 'missing', 'percentage']
 * @method static Collection getVendorTranslations(string $vendor, ?string $locale = null) Vendor package translations, optionally filtered by locale
 * @method static void setVendorTranslation(string $vendor, string $group, string $key, string $value, ?string $locale = null) Update a vendor translation value; throws ModelNotFoundException when the record does not exist
 *
 * @see \Rivalex\Lingua\Lingua
 */
class Lingua extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rivalex\Lingua\Lingua::class;
    }
}
