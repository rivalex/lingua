# Upgrade Guide

## Upgrading to v3.0

Versions before 3.0.0 are no longer installable from Packagist, so a `composer.lock` pinning one of them must be
updated to `^3.0`:

```bash
composer require rivalex/lingua:^3.0
```

### Lingua ships no CSS: add the `@source` line

Lingua no longer ships a stylesheet. The panel and the language selector follow your host theme, so your application
compiles their Tailwind classes. Add this line to `resources/css/app.css`, next to your Flux import:

```css
@source '../../vendor/rivalex/lingua/resources/views';
```

Then rebuild your assets (`npm run build`). `php artisan lingua:install` offers to add the line for you. Your
application needs Tailwind CSS v4 with Flux configured.

### Force dark mode is removed

The panel follows the dark mode of your host. Clean up what is no longer read:

- remove the `dark_mode` entry from a published `config/lingua.php`;
- remove `LINGUA_FORCE_DARK_MODE` from your `.env`;
- optionally delete the unused setting row: `DELETE FROM lingua_settings WHERE key = 'dark_mode.force'`.

### Published views and components

- A published view must drop `<x-lingua::dark-scope>` (the component is removed) and any link to the Lingua stylesheet
  (`lingua.min.css` is no longer shipped).
- The anonymous components now live under `resources/views/components` in the package, so
  `php artisan vendor:publish --tag=lingua-views` publishes them to `resources/views/vendor/lingua/components`. A
  published component there overrides the package component of the same name.

### Flags no longer come from `outhebox/blade-flags`

Lingua ships the flags it renders and no longer requires `outhebox/blade-flags` (nor `blade-ui-kit/blade-icons`). If
your application uses `@svg('flag-...')`, `svg('flag-...')` or `<x-flag-...>` components, require the package
directly:

```bash
composer require outhebox/blade-flags
```

### Alpine.js comes from Livewire

The Lingua JavaScript bundle no longer contains Alpine.js or the `@alpinejs/ui` and `@alpinejs/focus` plugins: Lingua
registers its components on the Alpine instance that Livewire loads. Lingua pages need Livewire's scripts, as they
already do with a standard layout. If your own pages relied on the `x-menu`, `x-listbox` or similar directives that
Lingua's bundle used to register, install `@alpinejs/ui` in your application.

### Service provider without `spatie/laravel-package-tools`

The service provider no longer uses `spatie/laravel-package-tools`. The publish tags keep their names and targets;
`lingua-views` now also writes the components (see "Published views and components"). `lingua:install` follows the
same steps plus the Tailwind `@source` step described above.

### The admin UI is denied until you define the ability

Lingua now requires the `manage-lingua` ability (`config('lingua.gate')`). Until your application defines it, access
is allowed only in the `local` environment and every other environment answers 403. Define it in your
`AppServiceProvider`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('manage-lingua', fn (User $user) => $user->is_admin);
```

To keep another ability name, set `LINGUA_GATE=your-ability`. Setting `LINGUA_GATE=` (empty) opts out and relies on
authentication only, which is not recommended. `role:` middleware in `lingua.middleware` is not re-checked on
Livewire requests: put the access rule in the gate.

If your published `config/lingua.php` still has `'gate' => env('LINGUA_GATE', null)`, the `manage-lingua` ability is now
enforced: define it as shown above. Only an empty string or `false` opts out.

### Clear the route cache

Run `php artisan route:clear` (or `optimize`) after upgrading: cached routes keep the gate middleware of the previous
configuration.

### Clear the cache

Clear the cache store Lingua uses after upgrading: the cached JSON strings (`<prefix>.<locale>.single`, prefix
`lingua.cache.prefix`, `lingua.trans` by default) changed shape. `php artisan cache:clear` clears the default store;
when `lingua.cache.store` (`LINGUA_CACHE_STORE`) names another store, run `php artisan cache:clear <store>`.

### Livewire 4.4.8 or newer

Update `livewire/livewire` to 4.4.8 or newer: `composer update livewire/livewire`. Earlier 4.x releases from 4.3.0 have a
published security advisory.

### Extension components must use `AuthorizesLingua`

An extension component that Lingua renders (for example a translation tab) and that does not use the
`Rivalex\Lingua\Traits\AuthorizesLingua` trait is no longer rendered. Add the trait to it.

### Flux 2.21 or newer

Update `livewire/flux` to 2.21 or newer before requiring Lingua 2.2: `composer update livewire/flux`.

### Notifications for new languages

If your published `config/lingua.php` has a `base_notifications_path` pointing to
`__DIR__.'/../resources/notifications'`, set it to `null`, otherwise notifications are not written for new languages.

### Markup is sanitized on write

`html` values always go through the sanitizer and are cleaned. `text` and `markdown` values that contain disallowed
HTML (scripts, event-handler attributes, unlisted tags) are rejected with a validation error, an import row error or an
exception instead of being rewritten. Truncated tags and sequences such as `a<b` are rejected too: write `a < b` (or
`a&lt;b` in a `markdown` value). Markdown autolinks (`<https://...>`) are not accepted: use `[text](url)`. Render
markdown values with
`Str::markdown($value, ['html_input' => 'strip', 'allow_unsafe_links' => false])` and text values with `{{ }}`.

Lingua decides this with its own tag scanner, independent of the libxml build of your PHP. `text` and `markdown` values
with HTML comments, unbalanced or mismatched tags, repeated attributes or unknown markup are rejected.

Existing stored values are not changed. When one is saved again, an `html` value is sanitized (so `class`, `style` or
`target` attributes are lost at that point) and a `text` or `markdown` value with disallowed HTML is rejected until it
is corrected.

### Group and key validation

Group and vendor names must match `^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$` (or be `single`). Keys must be non-empty, at most
300 characters, without leading, trailing or repeated whitespace, free of control characters and free of a `*` dot segment. Writes from the UI, import and facade that
break these rules are rejected. Outside `single`, keys must also be free of `*` and of empty dot segments. Files synchronized from your own `lang/` directory are not validated.

New keys and groups follow extra rules: a key may not contain `|`; a new key may not be a dot-prefix of an existing key
in the same group, nor have an existing key as its dot-prefix; a new group may not equal an existing group ignoring
case. `single` keys are literal in file mode.

### Livewire properties are locked

Livewire properties that no Lingua view binds are now locked. If you published the views or built custom views that
set such a property from the browser, stop doing so.

### The default language cannot be deleted

The UI and `Lingua::removeLanguage()` refuse to delete the default language. Change the default first.

### Commands exit with 1 on failure

`lingua:add`, `lingua:remove`, `lingua:update-lang` and `lingua:sync-to-local` now exit with code 1 after an error or a
refusal; they used to exit with 0. Check scripts and deploy pipelines that call them.

### Sync to local keeps keys that exist only in the files

Sync to local (the button, `lingua:sync-to-local`, the driver switch and the uninstall export) merges the database
values into the lang files and keeps the keys that exist only in the files. In database mode, a key you delete
in the UI is no longer removed from the lang files: delete it from your lang files yourself.

### Facade lookups use `group.key`

`Lingua::getTranslation()`, `getTranslations()`, `setTranslation()` and `forgetTranslation()` resolve `group.key`,
`vendor::group.key` and `single.<sentence>`. A bare key that more than one translation has (in different groups or vendors) finds nothing and changes nothing:
pass `group.key`.

### Settings no longer edits some options

The Settings page no longer edits `navigate`, `layout`, the translation links and the editor toolbar, because nothing
read the saved values. Set them in `config/lingua.php`.

### Spreadsheets exported before 3.0

To protect against formula injection, exports add a `'` prefix to cells that start with `=`, `+`, `-`, `@`, a tab or a carriage return, and
import removes one such prefix. Exports made before 3.0 did not add it to a value that already started with a quote
before such a character (for example `'=x`), so importing an older CSV, XLSX or ODS file drops that quote. Export again
with 3.0 before importing.

---

## Upgrading to v2.1

### Laravel 12 or later required

Lingua 2.1 supports Laravel 12 and 13. Laravel 11 is not supported: versions before 3.0.0 are no longer installable.

There are no other breaking changes: no config, migration or API change is needed.

---

## Upgrading to v2.0

### Why the major version?

`spatie/laravel-translation-loader` and `laravel-lang/common` have both been removed. Lingua now ships its own translation loading infrastructure, locale registry, and bundled translation dataset. This eliminates two external dependencies, removes a global cache-clear on every translation save, and adds storage-driver choice (database or file).

---

### Step 1 - Run `composer update`

```bash
composer update rivalex/lingua
```

Composer will automatically remove `spatie/laravel-translation-loader` from `vendor/`. If `laravel-lang/common` was in your own `composer.json`, remove it:

```bash
composer remove laravel-lang/common
```

---

### Step 2 - Publish the updated config

```bash
php artisan vendor:publish --tag=lingua-config --force
```

Or merge the new keys manually into your existing `config/lingua.php`:

```php
// NEW in 2.0 - merge these blocks if you have a published config

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

### Step 3 - Set the storage driver

Add to your `.env`:

```env
LINGUA_STORAGE_DRIVER=database
```

Then clear the config cache:

```bash
php artisan config:clear
```

The `database` driver is identical to v1.x behaviour. The `file` driver is new - see [Storage Drivers](#storage-drivers-new-in-20) below if you want to use it.

---

### Step 4 - Run migrations

The migration structure has changed. Three separate migration files now exist (previously one combined file):

- `create_language_lines_table`
- `create_languages_table`
- `create_lingua_settings_table`

If upgrading from v1.x with previously run migrations, no new migration is required - the tables already exist. If your existing published migrations differ from the package defaults, check for schema divergence.

---

### Step 5 - Update custom translation loaders

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

### Step 6 - Update custom Translation model extensions

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
        // your implementation - called by the Db translation loader
    }
}
```

---

### Step 7 - Replace type-hints on Spatie classes

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

### Step 8 - Remove `TranslationServiceProvider` from Testbench (package authors)

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

These facade methods are now DB-native - they create/delete the `Language` record and seed/clean up storage using Lingua's own bundled dataset. They no longer invoke `laravel-lang` commands and do not download translation files from GitHub.

**Who is affected:** code that relied on `lang:add` installing laravel-lang translation files automatically.

**Fix:** the bundled dataset (26 locales, 5902 strings, Laravel 13-aligned) is seeded automatically when you call `addLanguage()` or use the Languages UI. No action required for standard locales. If you need additional translation files beyond the bundled set, add them to your `lang/` directory manually or via `lingua:sync-to-database`.

For fully orchestrated operations (files + DB + sync), continue using the Artisan commands: `lingua:add {locale}` / `lingua:remove {locale}`.

---

#### Route middleware default - `['web', 'auth']`

Lingua admin routes now require authentication by default (was `['web']` - unauthenticated).

**Who is affected:** installations relying on the default to serve Lingua routes without login, or that provide their own auth via a different mechanism.

**Fix:** publish or update your config and set explicitly:

```php
// config/lingua.php
'middleware' => ['web'],  // if you want to keep unauthenticated access
```

**New: authorization gate** - for role-based access control, use `LINGUA_GATE`:

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

`routes_prefix` already supports route parameters - useful for multi-tenant apps:

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

By default Lingua uses the `database` driver - identical to v1.x behaviour. No action required.

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
| Invalidation on save | `Artisan::call('cache:clear')` - flushes entire app cache | `Cache::forget('lingua.trans.{locale}.{group}')` - surgical |
| Invalidation on delete | none | forgets all locale keys in the deleted record |
| Bulk sync | `cache:clear` in `finally` block | per-(locale,group) for touched keys only |
| Cache driver | app default | configurable via `LINGUA_CACHE_STORE` env |
| Cache prefix | n/a | `lingua.trans` (configurable via `LINGUA_CACHE_PREFIX`) |

---

### `Lingua::optimize()` deprecated

The `optimize()` method is deprecated and will be removed in a future version. It previously called `Artisan::call('cache:clear')` to flush the entire application cache. This is no longer necessary - translations are now invalidated surgically per `(locale, group)` on every save/delete.

Remove any calls to `Lingua::optimize()` from your codebase.
