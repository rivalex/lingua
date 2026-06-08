# Upgrade Guide

## Upgrading from v2.0 to v2.1

### Breaking change — `Lingua::info()` return type changed

`Lingua::info($locale)` now returns `?Rivalex\Lingua\Locales\LocaleInfo` instead of `LaravelLang\LocaleData`.

**Who is affected**: any code calling `Lingua::info()` and accessing properties of the returned object.

**Fix**: update property access:

| Old (`LocaleData`) | New (`LocaleInfo`) |
|---|---|
| `->locale->name` | `->name` |
| `->localized` | `->name` |
| `->direction->value` | `->direction` |
| `->regional` | `->regional` |
| `->native` | `->native` |
| `->code` | `->code` |
| `->type` | `->type` |

Method now returns `null` for unknown locales (previously threw). Guard with `if ($info === null)`.

---

### Breaking change — `addLanguage` / `removeLanguage` no longer write to filesystem

`Lingua::addLanguage()` and `Lingua::removeLanguage()` are now DB-native — they create/delete the `Language` record only. They no longer invoke `lang:add` / `lang:rm` and do not write or remove files from `lang_path()`.

**Who is affected**: code that relied on `lang:add` installing translation files automatically after calling `addLanguage()`.

**Fix**: pre-populate your `lang_path()` with translation files for each locale, or wait for Phase 2 bundled translations.

---

### Removed dependency — `laravel-lang/common`

`laravel-lang/common` is no longer installed by this package. Remove it from your own `composer.json` if you required it transitively.

---

### Breaking change — `navigate` default flipped to `false`

Lingua now defaults to standard full-page redirects instead of `wire:navigate`.

**Who is affected**: anyone relying on SPA navigation for locale switches or translation tab changes, without having explicitly set `'navigate' => true` in their published config.

**Fix**: publish or update your config and opt in:

```php
// config/lingua.php
'navigate' => true,
```

---

### New config keys

Publish the updated config or merge these keys manually:

```php
// config/lingua.php

/*
 * Layout for full-page route rendering. null = Livewire default (livewire.layout config).
 * Set to your project's layout, e.g. 'layouts.application'.
 */
'layout' => null,

/*
 * Use wire:navigate on internal redirects. Default is now false (opt-in).
 */
'navigate' => false,

/*
 * Optional URI suffix appended to all Lingua page routes.
 * Example: '{team?}' → /lingua/languages/{team?}
 */
'routes_extra_parameters' => null,
```

---

### Parameterized route prefixes (existing capability, now documented)

`routes_prefix` already supports route parameters — useful for multi-tenant apps:

```php
// config/lingua.php
'routes_prefix' => '{team}/lingua',
// Results in: /{team}/lingua/languages, /{team}/lingua/translations, ...
```

### Configurable translations link

External links to the translation management page (language row + statistics
missing-keys panel) and the in-page locale switcher redirect are now configurable:

```php
// config/lingua.php
'links' => [
    'translations' => [
        'enabled' => true,                    // false = render external links as plain text
        'route'   => 'lingua.translations',   // override with your own route name
    ],
],
```

`enabled` controls only external navigation links — the in-page locale selector
in the Translations component always redirects on locale change (using `route`).

For deeper Blade customization, publish views:

```bash
php artisan vendor:publish --tag=lingua-views
```

---

## Upgrading from v1.x to v2.0

### Why the breaking change?

`spatie/laravel-translation-loader` has been removed. Lingua now ships its own translation loading infrastructure, cache strategy, and contracts. This eliminates an external dependency and replaces the global `cache:clear` invalidation with targeted per-(locale, group) cache key invalidation.

---

### 1. Run `composer update`

```bash
composer update rivalex/lingua
```

Composer will remove `spatie/laravel-translation-loader` from your `vendor/` automatically.

---

### 2. Publish the updated config

```bash
php artisan vendor:publish --tag=lingua-config --force
```

The `lingua.cache` block is new. If you have a published config, merge it manually:

```php
// config/lingua.php
'cache' => [
    'store' => env('LINGUA_CACHE_STORE', null),  // null = default app cache
    'prefix' => env('LINGUA_CACHE_PREFIX', 'lingua.trans'),
],
```

---

### 3. Update custom translation loaders

If you wrote a custom loader registered in `config('lingua.translation_loaders')`, update the `implements` clause:

**Before:**
```php
use Spatie\TranslationLoader\TranslationLoaders\TranslationLoader;

class MyLoader implements TranslationLoader { }
```

**After:**
```php
use Rivalex\Lingua\Contracts\TranslationLoader;

class MyLoader implements TranslationLoader { }
```

The method signature `loadTranslations(string $locale, string $group, ?string $namespace = null): array` is unchanged.

---

### 4. Update custom Translation model extensions

If you extended `Spatie\TranslationLoader\LanguageLine`:

**Before:**
```php
use Spatie\TranslationLoader\LanguageLine;

class MyTranslation extends LanguageLine { }
```

**After:**
```php
use Illuminate\Database\Eloquent\Model;

class MyTranslation extends Model
{
    protected $table = 'language_lines';

    public static function getTranslationsForGroup(string $locale, string $group): array
    {
        // your implementation — required by Db loader
    }
}
```

---

### 5. Remove `TranslationServiceProvider` from Testbench (package authors)

```php
// Before
use Spatie\TranslationLoader\TranslationServiceProvider;

protected function getPackageProviders($app): array
{
    return [
        TranslationServiceProvider::class,  // remove
        LinguaServiceProvider::class,
    ];
}

// After
protected function getPackageProviders($app): array
{
    return [
        LinguaServiceProvider::class,
    ];
}
```

---

### 6. Replace type-hints on Spatie classes

```php
// Before
use Spatie\TranslationLoader\TranslationLoaderManager;
assert($loader instanceof TranslationLoaderManager);

// After
use Rivalex\Lingua\TranslationManager\LinguaManager;
assert($loader instanceof LinguaManager);
```

---

### Cache behaviour change

| | v1.x | v2.0 |
|---|---|---|
| Invalidation on save | `Artisan::call('cache:clear')` — flushes entire app cache | `Cache::forget('lingua.trans.{locale}.{group}')` — surgical |
| Invalidation on delete | none | forgets all locale keys in the deleted record |
| Bulk sync | `cache:clear` in `finally` block | unchanged |
| Cache driver | app default | configurable via `LINGUA_CACHE_STORE` env |
| Cache prefix | n/a | `lingua.trans` (configurable via `LINGUA_CACHE_PREFIX`) |
