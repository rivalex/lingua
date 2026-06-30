# Upgrade Guide

## Upgrading to v2.0

### Why the major version?

`spatie/laravel-translation-loader` and `laravel-lang/common` have both been removed. Lingua now ships its own translation loading infrastructure, locale registry, and bundled translation dataset. This eliminates two external dependencies, removes a global cache-clear on every translation save, and adds storage-driver choice (database or file).

---

### Step 1 — Run `composer update`

```bash
composer update rivalex/lingua
```

Composer will automatically remove `spatie/laravel-translation-loader` from `vendor/`. If `laravel-lang/common` was in your own `composer.json`, remove it:

```bash
composer remove laravel-lang/common
```

---

### Step 2 — Publish the updated config

```bash
php artisan vendor:publish --tag=lingua-config --force
```

Or merge the new keys manually into your existing `config/lingua.php`:

```php
// NEW in 2.0 — merge these blocks if you have a published config

// Authorization gate (role-based access control)
'gate' => env('LINGUA_GATE', null),

// Storage driver
'storage' => [
    'driver' => env('LINGUA_STORAGE_DRIVER', 'database'),
],

// Navigation menu
'nav' => [
    'enabled' => true,
],

// Use wire:navigate on internal redirects. Default is now false (opt-in).
'navigate' => false,

// Optional extra route suffix (multi-tenant)
'routes_extra_parameters' => null,

// Layout override for full-page route rendering
'layout' => null,

// Configurable translation page link
'links' => [
    'translations' => [
        'enabled' => true,
        'route'   => 'lingua.translations',
    ],
],

// Sticky filter bar offset
'ui' => [
    'sticky_top' => 0,
],

// Cache configuration
'cache' => [
    'store'  => env('LINGUA_CACHE_STORE', null),
    'prefix' => env('LINGUA_CACHE_PREFIX', 'lingua.trans'),
],

// Pro & extensions
'suppress_pro_nudge' => env('LINGUA_SUPPRESS_PRO_NUDGE', false),
'pro_upgrade_url'    => env('LINGUA_PRO_UPGRADE_URL', 'https://lingua.rivalex.dev'),
'extensions'         => [
    'enabled' => env('LINGUA_EXTENSIONS_ENABLED', true),
],
```

---

### Step 3 — Set the storage driver

Add to your `.env`:

```env
LINGUA_STORAGE_DRIVER=database
```

Then clear the config cache:

```bash
php artisan config:clear
```

The `database` driver is identical to v1.x behaviour. The `file` driver is new — see [Storage Drivers](#storage-drivers-new-in-20) below if you want to use it.

---

### Step 4 — Run migrations

The migration structure has changed. Three separate migration files now exist (previously one combined file):

- `create_language_lines_table`
- `create_languages_table`
- `create_lingua_settings_table`

If upgrading from v1.x with previously run migrations, no new migration is required — the tables already exist. If your existing published migrations differ from the package defaults, check for schema divergence.

---

### Step 5 — Update custom translation loaders

If you wrote a custom loader registered in `config('lingua.translation_loaders')`:

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

Method signature `loadTranslations(string $locale, string $group, ?string $namespace = null): array` is unchanged.

---

### Step 6 — Update custom Translation model extensions

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
        // your implementation — called by the Db translation loader
    }
}
```

---

### Step 7 — Replace type-hints on Spatie classes

If you type-hint the translation loader manager anywhere:

**Before:**
```php
use Spatie\TranslationLoader\TranslationLoaderManager;

assert($loader instanceof TranslationLoaderManager);
```

**After:**
```php
use Rivalex\Lingua\TranslationManager\LinguaManager;

assert($loader instanceof LinguaManager);
```

---

### Step 8 — Remove `TranslationServiceProvider` from Testbench (package authors)

```php
// Before
use Spatie\TranslationLoader\TranslationServiceProvider;

protected function getPackageProviders($app): array
{
    return [
        TranslationServiceProvider::class,  // remove this
        LinguaServiceProvider::class,
    ];
}

// After
protected function getPackageProviders($app): array
{
    return [LinguaServiceProvider::class];
}
```

---

### Breaking changes reference

#### `Lingua::info()` return type changed

`Lingua::info($locale)` now returns `?Rivalex\Lingua\Locales\LocaleInfo` instead of `LaravelLang\LocaleData`.

**Who is affected:** any code that calls `Lingua::info()` and reads properties of the result.

| Old (`LocaleData`)    | New (`LocaleInfo`) |
|-----------------------|--------------------|
| `->locale->name`      | `->name`           |
| `->localized`         | `->name`           |
| `->direction->value`  | `->direction`      |
| `->regional`          | `->regional`       |
| `->native`            | `->native`         |
| `->code`              | `->code`           |
| `->type`              | `->type`           |

The method now returns `null` for unknown locales (previously threw). Guard with `if ($info === null)`.

---

#### `addLanguage()` / `removeLanguage()` no longer call `lang:add` / `lang:rm`

These facade methods are now DB-native — they create/delete the `Language` record and seed/clean up storage using Lingua's own bundled dataset. They no longer invoke `laravel-lang` commands and do not download translation files from GitHub.

**Who is affected:** code that relied on `lang:add` installing laravel-lang translation files automatically.

**Fix:** the bundled dataset (26 locales, 5902 strings, Laravel 13-aligned) is seeded automatically when you call `addLanguage()` or use the Languages UI. No action required for standard locales. If you need additional translation files beyond the bundled set, add them to your `lang/` directory manually or via `lingua:sync-to-database`.

For fully orchestrated operations (files + DB + sync), continue using the Artisan commands: `lingua:add {locale}` / `lingua:remove {locale}`.

---

#### Route middleware default — `['web', 'auth']`

Lingua admin routes now require authentication by default (was `['web']` — unauthenticated).

**Who is affected:** installations relying on the default to serve Lingua routes without login, or that provide their own auth via a different mechanism.

**Fix:** publish or update your config and set explicitly:

```php
// config/lingua.php
'middleware' => ['web'],  // if you want to keep unauthenticated access
```

**New: authorization gate** — for role-based access control, use `LINGUA_GATE`:

```env
LINGUA_GATE=manage-translations
```

```php
// AppServiceProvider or AuthServiceProvider
Gate::define('manage-translations', fn (User $user) => $user->hasRole('admin'));
```

---

#### `navigate` default → `false`

Lingua now defaults to full-page redirects (standard behaviour) instead of `wire:navigate`.

**Who is affected:** anyone relying on SPA navigation for locale switches or page changes without having explicitly set `'navigate' => true`.

**Fix:**

```php
// config/lingua.php
'navigate' => true,
```

---

#### Parameterized route prefixes

`routes_prefix` already supports route parameters — useful for multi-tenant apps:

```php
// config/lingua.php
'routes_prefix' => '{team}/lingua',
// Results in: /{team}/lingua/languages, /{team}/lingua/translations, ...
```

The new `routes_extra_parameters` key appends a suffix to all page routes without modifying the prefix:

```php
// config/lingua.php
'routes_extra_parameters' => '{team?}',
// Example: /lingua/languages/{team?}
```

---

#### laravel-lang translation files no longer auto-installed

`lingua:update-lang` no longer downloads from laravel-lang. It re-syncs translations for installed locales from the bundled dataset and your local `lang/` files.

**Who is affected:** workflows that relied on `lingua:update-lang` to pull the latest laravel-lang strings.

**Fix:** the bundled dataset (aligned to Laravel 13) covers auth, pagination, passwords, validation, http-statuses, errors, and notifications for 26 locales. For other translation groups (e.g. country names, currencies), add them to your `lang/` files manually and run `lingua:sync-to-database`.

---

#### PHP version requirement

Lingua 2.0 requires **PHP 8.3** or higher (was 8.1+).

---

### Storage drivers (new in 2.0)

By default Lingua uses the `database` driver — identical to v1.x behaviour. No action required.

If you want to switch to file-based storage:

```bash
php artisan lingua:storage file
```

This will:
1. Export all database translations to `lang/` files
2. Switch the driver
3. Warn about html/markdown type-loss (file mode stores only plain text)

See the Storage Drivers section in the README for trade-offs and deploy caveats.

---

### New commands

| Command | Description |
|---------|-------------|
| `lingua:storage {driver}` | Switch storage driver; syncs translations before switching |
| `lingua:uninstall` | Safely remove Lingua (exports translations first) |

---

### Cache behaviour change

| | v1.x | v2.0 |
|---|---|---|
| Invalidation on save | `Artisan::call('cache:clear')` — flushes entire app cache | `Cache::forget('lingua.trans.{locale}.{group}')` — surgical |
| Invalidation on delete | none | forgets all locale keys in the deleted record |
| Bulk sync | `cache:clear` in `finally` block | per-(locale,group) for touched keys only |
| Cache driver | app default | configurable via `LINGUA_CACHE_STORE` env |
| Cache prefix | n/a | `lingua.trans` (configurable via `LINGUA_CACHE_PREFIX`) |

---

### `Lingua::optimize()` deprecated

The `optimize()` method is deprecated and will be removed in a future version. It previously called `Artisan::call('cache:clear')` to flush the entire application cache. This is no longer necessary — translations are now invalidated surgically per `(locale, group)` on every save/delete.

Remove any calls to `Lingua::optimize()` from your codebase.
