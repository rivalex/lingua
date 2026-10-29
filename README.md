<div align="center">

<a href="https://rivalex.github.io/lingua-docs/" target="_blank" style="margin: 30px 0 50px !important;">
<figure>
<img src="src/dist/images/logoLinguaHorizzontal.svg" alt="Lingua Logo" width="500">
</figure>
</a>

---

### **The complete multilingual management system for Laravel**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rivalex/lingua.svg)](https://packagist.org/packages/rivalex/lingua)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12--13-orange)](https://laravel.com)
[![License](https://img.shields.io/github/license/rivalex/lingua)](LICENSE.md)
[![codecov](https://codecov.io/github/rivalex/lingua/branch/master/graph/badge.svg?token=9RKRB8AYD6)](https://codecov.io/github/rivalex/lingua)
[![Tests](https://github.com/rivalex/lingua/actions/workflows/run-tests.yml/badge.svg)](https://github.com/rivalex/lingua/actions/workflows/run-tests.yml)

<br>

<img src="src/dist/images/lingua-workflow.gif" alt="Lingua in action - add a language, edit a translation, reorder languages, and track coverage from the Livewire admin UI" width="800">

<br>

**Lingua** is a self-hosted translation management package for Laravel - a native, database-driven alternative to
tools like Weblate, Locize, Lokalise, Crowdin, and SimpleLocalize, and a drop-in replacement for
`spatie/laravel-translation-loader` and `laravel-lang/common`. If you need to manage translations in a Laravel
application without depending on an external SaaS or downloading third-party language packages, Lingua gives you a
complete Livewire admin UI to edit, organize, import, export, and synchronize your i18n / l10n strings directly inside
your app.

Built on the TALL stack (Tailwind, Alpine, Livewire), Lingua stores translations in either your **database** or your
native `lang/` **files** - switchable with a single config value. It ships a bundled dataset of 26 locales aligned to
Laravel 13 (no `laravel-lang` required), handles published vendor translations safely, supports **CSV, JSON, XLSX, and
ODS** import/export with a mandatory dry-run preview, offers full RTL support, and can optionally AI-translate missing
strings via Lingua Pro. Built for **Laravel 12–13, Livewire 4, and PHP 8.3+**.

[Features](#-features) · [Installation](#-installation) · [Configuration](#-configuration) · [Storage Drivers](#-storage-drivers) · [Artisan Commands](#-artisan-commands) · [Publishing](#-publishing) · [UI Guide](#-ui-guide) · [Import / Export](#-import--export) · [Language Selector](#-language-selector) · [Facade](#-lingua-facade) · [Architecture](#-architecture) · [Lingua Pro](#-lingua-pro)

</div>

---

<div align="center">

## [Official Documentation](https://rivalex.github.io/lingua-docs/)

</div>

---

## ✨ Features

| Feature                          | Description                                                                                      |
|----------------------------------|--------------------------------------------------------------------------------------------------|
| **Database-backed translations** | All translations stored in the database, editable instantly without deployments                  |
| **File-mode driver**             | Optional file-mode stores translations directly in lang/ PHP/JSON files - no database required   |
| **Livewire UI**                  | Reactive, real-time language and translation management interface                                |
| **Flux UI components**           | Modern, accessible UI built with Livewire Flux                                                   |
| **Bi-directional sync**          | Push translations to the database or pull them back to local PHP/JSON files                      |
| **Import / Export**              | Export and import translations as CSV, JSON, XLSX, or ODS; bilingual and multi-locale scopes     |
| **Bundled translation dataset**  | 26 locales × 7 groups (5 902 strings) aligned to Laravel 13 - provisioned at locale install-time |
| **Rich text support**            | Translations can be plain text, HTML, or Markdown                                                |
| **Language selector**            | Configurable sidebar, dropdown, modal, or headless language switcher                             |
| **Translation statistics**       | Per-language coverage with progress bars, group breakdown, and missing-key drill-down            |
| **Shared navigation menu**       | Built-in nav bar on all admin pages; active-page highlighting; toggleable                        |
| **Host theme**                   | Follows your Tailwind theme: fonts, neutrals, accent and dark mode come from the host            |
| **DB-persisted settings**        | Selector mode, flags, nav, sticky offset managed from the UI                                        |
| **Headless language selector**   | Zero-CSS semantic HTML component for full styling freedom                                        |
| **RTL support**                  | First-class right-to-left language handling                                                      |
| **Vendor translations**          | Manage package translations alongside your own                                                   |
| **Authorization gate**           | Deny by default, access control via the `manage-lingua` Laravel Gate (`LINGUA_GATE` to rename)   |
| **Database-agnostic**            | Full support for SQLite, MySQL, PostgreSQL, and SQL Server                                       |
| **Safe uninstall**               | `lingua:uninstall` exports translations before removing tables and published files               |
| **Lingua Facade**                | Fluent programmatic API for reading, writing, and managing languages and translations            |
| **Flexible routing**             | Route prefix/suffix config, gate name, layout override, `wire:navigate` toggle                   |
| **Lingua Pro**                   | Optional add-on: AI translation, translation memory, glossary, usage analytics                   |
| **Fully tested**                 | 1,000+ tests with Pest, covering commands, Livewire components, Blade, helpers, and facade         |

---

## 📦 Requirements

- PHP **8.3+**
- Laravel **12 | 13**
- Livewire **4.4.8+**
- Livewire Flux **2.21+**
- Tailwind CSS **v4** with Flux configured in the host (`@import` of `flux.css` in `resources/css/app.css`)

**Optional:**

- `openspout/openspout` - required for XLSX and ODS import/export
- `rivalex/lingua-pro` - AI translation, translation memory, glossary, and analytics

---

## 🚀 Installation

### 1. Install via Composer

```bash
composer require rivalex/lingua
```

### 2. Run the interactive installer

```bash
php artisan lingua:install
```

The installer will:

- Prompt you to choose a storage driver: **database** (recommended) or **file**
- Publish `config/lingua.php`
- Publish and run the database migrations (driver-aware: file mode skips the `language_lines` table)
- Seed the database with your default language and its bundled translations (database mode)
- Or write default lang/ files (file mode)

After installation, set `LINGUA_STORAGE_DRIVER=database` (or `file`) in your `.env` and run `php artisan config:clear`.

#### Styling

Lingua ships no CSS. Your application compiles the panel's Tailwind classes with its own stylesheet, so add one line to
`resources/css/app.css`, next to your Flux import:

```css
@source '../../vendor/rivalex/lingua/resources/views';
```

Then rebuild your assets:

```bash
npm run build
```

The installer offers to add the line for you: with your confirmation it inserts it after the last `@source` line of
`resources/css/app.css` (or after the last `@import` line when there is none, or at the top when the file has neither). If you decline, or with
`--no-interaction`, or when the file does not exist, it changes nothing and prints the line to add. It never writes any
other file.

#### Theme

The panel and the language selector use only your host theme: fonts, neutral colours, the Flux accent colour and dark
mode all come from your application. Lingua has no dark mode switch of its own; it follows the dark mode of your host
(for example `<html class="dark">`).

### 3. Securing the admin UI

Lingua is deny by default. Every admin page and every admin Livewire component checks the ability named by
`config('lingua.gate')`, which defaults to `manage-lingua`. Define it in your `AppServiceProvider` (or any provider of
your application):

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('manage-lingua', fn (User $user) => $user->is_admin);
```

- **Until you define the ability**, Lingua registers a fallback that allows access only in the `local` environment.
  Everywhere else the admin UI answers `403`.
- **Another ability name**: set `LINGUA_GATE=manage-translations` and define that ability instead.
- **Opt out**: set an empty value (`LINGUA_GATE=`) to rely on authentication only. This is not recommended.
- **Embedding is safe**: each admin Livewire component authorizes the gate itself, so a component embedded in one of
  your own pages is protected too.
- **`role:` middleware is not enough**: on Livewire requests only `auth` and the `can:` gate are checked again. Other
  middleware set in `lingua.middleware` (for example `role:admin`) runs on the initial page load only, so put the access
  rule in the gate.

### 4. Access the UI

| Page         | URL                                           | Route name            |
|--------------|-----------------------------------------------|-----------------------|
| Languages    | `your-app.test/lingua/languages`              | `lingua.languages`    |
| Translations | `your-app.test/lingua/translations/{locale?}` | `lingua.translations` |
| Statistics   | `your-app.test/lingua/statistics`             | `lingua.statistics`   |
| Settings     | `your-app.test/lingua/settings`               | `lingua.settings`     |
| Transfer     | `your-app.test/lingua/transfer`               | `lingua.transfer`     |

---

## ⚙️ Configuration

After installation, `config/lingua.php` gives you full control:

```php
return [

    // -------------------------------------------------------------------------
    // LOCALE DEFAULTS
    // -------------------------------------------------------------------------

    // Directory where local language files are stored
    'lang_dir'         => lang_path(),
    'default_locale'   => config('app.locale', 'en'),
    'fallback_locale'  => config('app.fallback_locale', 'en'),
    'session_variable' => 'locale',

    // -------------------------------------------------------------------------
    // ROUTING
    // -------------------------------------------------------------------------

    // Middleware applied to Lingua's management routes (auth required by default)
    'middleware' => ['web', 'auth'],

    // Authorization gate, deny by default. 'can:{gate}' is added to admin routes and
    // every admin component checks it. Empty string = authentication only.
    'gate' => env('LINGUA_GATE', 'manage-lingua'),

    // URL prefix for Lingua's management pages
    'routes_prefix' => 'lingua',

    // Optional URI fragment appended to every Lingua page route.
    // Useful for multi-tenant routing, e.g. '{team?}'.
    'routes_extra_parameters' => null,

    // -------------------------------------------------------------------------
    // UI / PRESENTATION
    // -------------------------------------------------------------------------

    // Language selector widget
    'selector' => [
        'mode'       => 'sidebar',   // 'sidebar' | 'modal' | 'dropdown' | 'headless'
        'show_flags' => true,
    ],

    // Rich-text editor toolbar options (configured here only)
    'editor' => [
        'headings'      => false,
        'bold'          => true,
        'italic'        => true,
        'underline'     => true,
        'strikethrough' => false,
        'subscript'     => true,
        'superscript'   => true,
        'blockquote'    => false,
        'code-line'     => false,
        'code-block'    => false,
        'bullet'        => true,
        'ordered'       => true,
        'clear'         => true,
        'code-mode'     => false,
    ],

    // Layout used when Lingua pages render full-page via lingua routes.
    // null = use the Livewire default (livewire.layout config).
    'layout' => null,

    // Set to true when the host app has Livewire SPA navigation enabled
    // (adds wire:navigate to internal locale/page redirects).
    'navigate' => false,

    // Configurable navigation links from the Lingua UI to package pages.
    'links' => [
        'translations' => [
            'enabled' => true,
            'route'   => 'lingua.translations',
        ],
    ],

    // Shared navigation menu (shown on all Lingua admin pages).
    'nav' => [
        'enabled' => true,
    ],

    // UI presentation tweaks.
    // sticky_top: CSS top offset for the sticky filter bar in the Translations page.
    // Use when the host app has a fixed header. Accepts int (→ rem) or a CSS string.
    'ui' => [
        'sticky_top' => 0,
    ],

    // -------------------------------------------------------------------------
    // STORAGE / LOADERS
    // -------------------------------------------------------------------------

    'storage' => [
        'driver' => env('LINGUA_STORAGE_DRIVER', 'database'),  // 'database' | 'file'
    ],

    'translation_loaders' => [\Rivalex\Lingua\Database\Db::class],
    'model'               => \Rivalex\Lingua\Models\Translation::class,
    'translation_manager' => \Rivalex\Lingua\TranslationManager\LinguaManager::class,

    // Base path for bundled translation dataset (leave null for package default)
    'base_translations_path'  => null,
    'base_notifications_path' => null,  // managed internally

    // Cache configuration: translations cached forever per (locale, group) pair.
    'cache' => [
        'store'  => env('LINGUA_CACHE_STORE', null),           // null = app default driver
        'prefix' => env('LINGUA_CACHE_PREFIX', 'lingua.trans'),
    ],

    // -------------------------------------------------------------------------
    // LINGUA PRO
    // -------------------------------------------------------------------------

    // Set to true when Lingua Pro is installed to suppress the upgrade nudge in the admin UI.
    'suppress_pro_nudge' => env('LINGUA_SUPPRESS_PRO_NUDGE', false),
    'pro_upgrade_url'    => env('LINGUA_PRO_UPGRADE_URL', 'https://lingua.rivalex.dev'),

    // -------------------------------------------------------------------------
    // EXTENSIONS
    // -------------------------------------------------------------------------

    'extensions' => [
        'enabled' => env('LINGUA_EXTENSIONS_ENABLED', true),
    ],
];
```

### Environment variables

| Variable                    | Default                      | Description                               |
|-----------------------------|------------------------------|-------------------------------------------|
| `LINGUA_STORAGE_DRIVER`     | `database`                   | Storage driver: `database` or `file`      |
| `LINGUA_GATE`               | `manage-lingua`              | Laravel Gate name for admin authorization |
| `LINGUA_CACHE_STORE`        | `null`                       | Cache store (null = app default)          |
| `LINGUA_CACHE_PREFIX`       | `lingua.trans`               | Cache key prefix                          |
| `LINGUA_SUPPRESS_PRO_NUDGE` | `false`                      | Hide the Lingua Pro upgrade prompt        |
| `LINGUA_PRO_UPGRADE_URL`    | `https://lingua.rivalex.dev` | Pro upgrade link shown in admin UI        |
| `LINGUA_EXTENSIONS_ENABLED` | `true`                       | Enable/disable the extension system       |

Hosts that share one cache store between tenants must set a different `LINGUA_CACHE_PREFIX` per tenant, otherwise the
tenants read each other's cached translations. Also note that `lingua:uninstall` drops the `language_lines` table, a
table name that `spatie/laravel-translation-loader` uses as well.

---

## 💾 Storage Drivers

<p align="center">
  <img src="src/dist/images/languages-page.png" alt="Lingua Languages page - manage installed locales with per-language translation coverage bars" width="850">
</p>

Lingua supports two storage backends, selectable at install time or switched later.

### `database` (recommended)

- Translations stored in the `language_lines` table (one JSON `text` column, all locales per row)
- Runtime DB queries cached `rememberForever` per `(locale, group)` pair; surgical invalidation on save/delete
- A cache store outage degrades to direct database reads (reported, then skipped for the rest of the request); it never breaks rendering
- Full-featured: HTML/Markdown translation types, vendor translation guard, statistics, import/export
- Works with SQLite, MySQL, PostgreSQL, and SQL Server

### `file`

- Reads and writes lang/ PHP/JSON files directly - no `language_lines` table required
- Lightweight: suitable for simple projects or when a database is unavailable
- Translation types are flattened to plain text when files are written
- Sync buttons (Sync to DB / Sync to Local) are hidden in the UI
- `lingua:sync-to-local` is a no-op unless `--force` is passed
- Deploy pipelines (Forge / Envoyer / CI) may overwrite lang/ files - coordinate with your deployment strategy

### Switching drivers

```bash
# Switch to database driver (syncs file→DB, runs language_lines migration)
php artisan lingua:storage database

# Switch to file driver (syncs DB→lang/, warns on HTML/Markdown type-loss)
php artisan lingua:storage file
```

Or set the env variable directly:

```ini
# .env
LINGUA_STORAGE_DRIVER=database
```

Then run `php artisan config:clear`.

**Options for `lingua:storage`:**

| Option         | Description                                                      |
|----------------|------------------------------------------------------------------|
| `--force`      | Skip confirmation prompts                                        |
| `--write-env`  | Automatically update `LINGUA_STORAGE_DRIVER` in your `.env` file |
| `--no-migrate` | Skip running migrations when switching to database driver        |

> **File-mode deploy caveat:** In CI/CD pipelines, lang/ files are typically checked in to version control. Any
> translation edited in the Lingua UI will modify those files. Ensure your deployment pipeline does not overwrite them
> with stale copies. HTML and Markdown translation types will be lost when switching from database to file mode, as
> file-based drivers store only plain text.

---

## 🛠 Artisan Commands

Lingua ships with a complete command suite for terminal-driven language and translation management.

### Language management

| Command                  | Description                                                                |
|--------------------------|----------------------------------------------------------------------------|
| `lingua:add {locale}`    | Install a new language: creates DB/file record, seeds bundled translations |
| `lingua:remove {locale}` | Remove a language: deletes record and cleans up storage                    |
| `lingua:update-lang`     | Re-sync translations for all installed locales                             |

```bash
# Add Italian
php artisan lingua:add it

# Add Brazilian Portuguese
php artisan lingua:add pt_BR

# Remove French (the default language is protected)
php artisan lingua:remove fr

# Re-sync translation strings for all installed locales
php artisan lingua:update-lang
```

> **Note:** `lingua:add` and `lingua:remove` are **DB-native** - they do not use `laravel-lang`. The bundled dataset
> provides translations for 26 locales automatically. Lingua does not require or download any external language packages.

### Translation sync

| Command                          | Description                                                                             |
|----------------------------------|-----------------------------------------------------------------------------------------|
| `lingua:sync-to-database`        | Import all local lang/ PHP/JSON files into the database                                 |
| `lingua:sync-to-local [--force]` | Merge database translations into lang/ files (`--force` overrides file-mode no-op guard) |

```bash
# Populate the database from existing lang/ files (e.g. after a fresh install)
php artisan lingua:sync-to-database

# Write database translations to lang/ files (e.g. for version control or deployment)
php artisan lingua:sync-to-local

# Force export even in file-mode (advanced use)
php artisan lingua:sync-to-local --force
```

### Setup & maintenance

| Command                             | Description                                         |
|-------------------------------------|-----------------------------------------------------|
| `lingua:install`                    | Interactive first-time setup wizard                 |
| `lingua:storage {driver} [options]` | Switch storage driver between `database` and `file` |
| `lingua:uninstall [options]`        | Safely remove Lingua (exports translations first)   |

#### `lingua:install`

Interactive setup wizard. Prompts for driver selection (arrow-key), publishes config, publishes driver-aware migrations,
optionally runs them, and seeds default language data.

```bash
php artisan lingua:install
```

#### `lingua:storage {driver}`

Switch between `database` and `file` storage without data loss. Syncs translations before switching and warns on
HTML/Markdown type-loss when moving to file mode.

```bash
php artisan lingua:storage database --write-env
php artisan lingua:storage file --force
```

| Option         | Description                                            |
|----------------|--------------------------------------------------------|
| `--force`      | Skip confirmation prompts                              |
| `--write-env`  | Update `LINGUA_STORAGE_DRIVER` in `.env` automatically |
| `--no-migrate` | Skip running migrations (database driver switch only)  |

#### `lingua:uninstall`

Exports all translations to lang/ files first, then drops the three Lingua database tables and removes published files.
Your lang/ directory is always preserved.

```bash
php artisan lingua:uninstall
```

| Option             | Description                                    |
|--------------------|------------------------------------------------|
| `--force`          | Skip confirmation prompts                      |
| `--keep-config`    | Do not delete `config/lingua.php`              |
| `--keep-published` | Do not delete published views and translations |

---

## 📤 Publishing

Lingua ships several publishable groups so you can override only what you need.

### Publish everything at once

```bash
php artisan vendor:publish --provider="Rivalex\Lingua\LinguaServiceProvider"
```

### Publish individual tags

#### `lingua-config`

Publishes the configuration file to `config/lingua.php`.

```bash
php artisan vendor:publish --tag="lingua-config"
```

Use this when you want to customise routes, middleware, the language selector mode, the rich-text editor toolbar, or any
other package option. The file is well-commented and safe to edit - Lingua reads it on every request.

---

#### `lingua-migrations`

Publishes the database migrations to `database/migrations/`.

```bash
php artisan vendor:publish --tag="lingua-migrations"
```

Driver-aware: file mode only publishes the `languages` and `lingua_settings` table migrations (skips `language_lines`).
Use this when you need to modify the schema - for example to add indexes or change column types. After publishing, run
`php artisan migrate` as normal.

> **Note:** The `lingua:install` wizard publishes the migrations and runs `migrate` only when you confirm the prompt.
> After that prompt, if the Lingua tables exist, it runs the seeder (database mode) or installs the default language
> (file mode). In a non-interactive install, run `php artisan migrate` and
> `php artisan db:seed --class="Rivalex\Lingua\Database\Seeders\LinguaSeeder"` yourself. Only publish manually if you
> need to customise the schema before running them.

---

#### `lingua-translations`

Publishes the package's own UI translation strings to `lang/vendor/lingua/`.

```bash
php artisan vendor:publish --tag="lingua-translations"
```

This exposes all the labels, headings, buttons, and messages used in the Lingua admin UI. The admin interface is
localised in **9 languages**: Arabic, English, Spanish, French, Hindi, Italian, Portuguese, Russian, and Chinese (
Simplified). Override any string to adapt the wording to your project's style.

---

#### `lingua-views`

Publishes all Blade and Livewire views to `resources/views/vendor/lingua/`.

```bash
php artisan vendor:publish --tag="lingua-views"
```

The full view tree is:

```
resources/views/vendor/lingua/
├── components/               # Blade anonymous components (<x-lingua::...>)
│   ├── branding, card, clipboard, editor, flag, language-flag, menu-group,
│   ├── message, nav, select, stat, type-icon, validation-errors, ...
│   ├── card/  editor/  select/
├── selector/                 # Language selector views
│   ├── sidebar.blade.php, dropdown.blade.php, modal.blade.php,
│   └── headless.blade.php, icon.blade.php
├── languages.blade.php       # Livewire pages
├── translations.blade.php
├── statistics.blade.php      # + statistics/partials/
├── settings.blade.php        # + settings/partials/
├── transfer.blade.php        # + transfer/partials/
├── export.blade.php
├── import.blade.php
├── language/                 # create, delete, row, set-default, sort, table
└── translation/              # create, delete, row, update
```

> **Tip:** Only publish views you intend to change. Unpublished views are served directly from the package and will
> receive upstream updates automatically.

---

### Re-publishing after upgrades

After updating Lingua via Composer, re-publish any tags that may have changed:

```bash
# Force-overwrite previously published translations
php artisan vendor:publish --tag="lingua-translations" --force
```

The `--force` flag overwrites existing files. Omit it for views and config so your local customisations are not lost.

> **Note:** JavaScript assets and images are served directly from the package via Lingua's own asset route (
`lingua.assets`). Publishing assets to `public/` is not required.

---

## 🖥 UI Guide

### Languages page - `/lingua/languages`

The languages page is your control center for installed locales.

**Available actions:**

- **Add a language** - choose from 26 bundled locales; the record is created and bundled translations are seeded
  automatically
- **Remove a language** - confirmation modal prevents accidental deletion; the default language is protected
- **Set the default language** - one click sets the new application default
- **Reorder languages** - drag-and-drop to control display order across the UI
- **Sync to database** - import all local lang/ files into the database *(database mode only)*
- **Sync to local** - merge database translations into the lang/ files; keys that exist only in the files are
  kept *(database mode only)*
- **Update translations** - re-sync strings for all installed locales

Each language row shows the **completion percentage** and a count of **missing translations** so you can prioritise your
translation effort. Sync buttons are hidden in file mode.

### Translations page - `/lingua/translations/{locale?}`

<p align="center">
  <img src="src/dist/images/translations-page.png" alt="Lingua Translations page - edit translation strings inline in a filterable, paginated table" width="850">
</p>

Manage individual translation strings with a filterable, paginated table.

**Available actions:**

- **Search** by key, group, or value
- **Filter** by locale, group, or translation type (text / HTML / Markdown)
- **Show only missing** translations for a locale to focus your translation work
- **Create** new custom translation entries
- **Edit** any string inline - the rich-text editor activates automatically for HTML and Markdown types
- **Delete** translations globally or for a specific locale only
- **Copy** the translation key to clipboard with one click

The filter bar is sticky with a configurable top offset (`ui.sticky_top`) for apps with a fixed header.

### Statistics page - `/lingua/statistics`

<p align="center">
  <img src="src/dist/images/statistics-coverage.png" alt="Lingua Statistics page - per-language translation coverage with progress bars and missing-key counts" width="850">
</p>

The statistics page gives you a bird's-eye view of your translation coverage.

**What it shows:**

- **Per-language coverage** - progress bar for each installed locale with the percentage of translated keys and a count
  of missing ones
- **Group breakdown** - table showing how many keys are translated per locale across each translation group
- **Missing-key drill-down** - click the missing count for any language to expand a list of untranslated keys with
  direct links to the translation editor
- **Vendor toggle** - include or exclude vendor translations from all statistics with a single switch

```blade
<a href="{{ route('lingua.statistics') }}">Translation Statistics</a>
```

### Settings page - `/lingua/settings`

The settings page lets you configure Lingua's UI behaviour without touching config files or redeploying.

**What you can configure:**

- **Selector mode** - choose between `sidebar`, `modal`, `dropdown`, or `headless`
- **Flag icons** - toggle country flag icons next to language names
- **Navigation menu** - enable or disable the shared nav bar shown on all Lingua pages
- **Sticky bar offset** - set the top offset of the sticky filter bar (`ui.sticky_top`)

The `wire:navigate` toggle, the layout, the translation links and the editor toolbar are not edited here: set `navigate`,
`layout`, `links` and `editor` in `config/lingua.php`.

Settings are stored in the `lingua_settings` database table and take effect immediately. Values from `config/lingua.php`
serve as fallback when no database setting has been saved.

```blade
<a href="{{ route('lingua.settings') }}">Lingua Settings</a>
```

### Transfer page - `/lingua/transfer`

The transfer page provides file-based import and export of translation data.

- **Export** - select a scope and format, download the file
- **Import** - upload a file, review a dry-run diff, confirm to commit

See [Import / Export](#-import--export) for full details.

---

### RTL / LTR text direction

Some languages (Arabic, Hebrew, Persian, Urdu, …) are written right-to-left. Lingua stores the text direction for every
installed language and exposes it via `Lingua::getDirection()`. Add `dir` and `lang` attributes to your main Blade
layout:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ Lingua::getDirection() }}">
```

`Lingua::getDirection()` returns `'rtl'` for right-to-left languages and `'ltr'` for all others. Pass an explicit locale
when needed:

```blade
<html lang="ar" dir="{{ Lingua::getDirection('ar') }}">
```

#### Tailwind CSS

The `dir` attribute on `<html>` activates Tailwind's built-in `rtl:` variant automatically:

```html

<div class="text-left rtl:text-right">…</div>
<div class="pl-4 rtl:pr-4 rtl:pl-0">…</div>
```

#### Checking direction in Blade

```blade
@if (Lingua::getDirection() === 'rtl')
    {{-- RTL-specific markup or classes --}}
@endif
```

> **Note:** `Lingua::getDirection()` defaults to `'ltr'` if the locale is not found, so it is always safe to call even
> before any language is installed.

### Flux UI Assets

Lingua uses [Flux UI](https://fluxui.dev) for its interface. Include Flux assets in your application layout:

```html
{{-- resources/views/layouts/app.blade.php --}}
<head>
    ...
    @fluxAppearance
</head>
<body>
...
@fluxScripts
</body>
```

---

## 📦 Import / Export

The Transfer page (`/lingua/transfer`) provides Livewire-powered export and import of translation data.

### Export

Three export scopes:

| Scope            | Description                                            |
|------------------|--------------------------------------------------------|
| **Bilingual**    | Source strings + one target locale (two columns)       |
| **Multi-locale** | Source + all installed locales (one column per locale) |
| **JSON-native**  | Raw key/value JSON without a source column             |

Four export formats:

| Format   | Dependencies                   |
|----------|--------------------------------|
| **CSV**  | Built-in - no extra packages   |
| **JSON** | Built-in - no extra packages   |
| **XLSX** | Requires `openspout/openspout` |
| **ODS**  | Requires `openspout/openspout` |

```bash
composer require openspout/openspout
```

Formula-injection guard: cells starting with `= + - @` are automatically prefixed with `'` in all spreadsheet formats to
prevent formula execution.

### Import

<p align="center">
  <img src="src/dist/images/transfer-import-diff.png" alt="Lingua import dry-run preview - create, update, skip and error counts with a row-by-row diff before committing" width="850">
</p>

The import flow is a two-step process:

1. **Upload** - select your file; Lingua runs a dry-run analysis and shows a preview: create / update / skip / error
   counts and a capped row-by-row diff
2. **Review & confirm** - inspect the diff, then commit when ready

Import behavior:

- Commit is **transactional** in database mode (all-or-nothing) and sequential in file mode
- Vendor translations are **protected** - import cannot create or delete vendor-namespaced keys
- **Bilingual single-column fallback**: when a bilingual file has exactly one data column and the locale code doesn't
  exactly match the header (e.g. `it_IT` vs `it - Italian`), Lingua uses that column automatically

---

## 🌐 Language Selector

<p align="center">
  <img src="src/dist/images/selector-modal.png" alt="Lingua language selector - modal language switcher with flags for your application's end users" width="550">
</p>

Embed a language switcher anywhere in your Blade layouts:

```blade
<livewire:lingua::language-selector />
```

Control the display mode via config or inline props:

```blade
{{-- sidebar (default), dropdown, modal, or headless --}}
<livewire:lingua::language-selector mode="dropdown" :show-flags="false" />
```

The selector reads the active mode and flag preference from the database (configurable in the Settings page). The
`:mode` and `:show-flags` props override the database setting for a specific instance.

### Headless mode

The headless selector renders zero CSS and no framework-specific markup - just semantic HTML that you style entirely
with your own CSS or utility classes.

```blade
<livewire:lingua::headless-language-selector />
```

**Named slots:**

Override individual language items using the `$item` slot (receives the `Language` model):

```blade
<livewire:lingua::headless-language-selector>
    <x-slot:item="language">
        {{ $language->native }} ({{ $language->code }})
    </x-slot>
</livewire:lingua::headless-language-selector>
```

Override the **currently selected** language with the `$current` slot (falls through to `$item` if not provided):

```blade
<livewire:lingua::headless-language-selector>
    <x-slot:current="language">
        <strong>{{ $language->native }}</strong>
    </x-slot>
    <x-slot:item="language">
        {{ $language->native }}
    </x-slot>
</livewire:lingua::headless-language-selector>
```

**CSS targeting API (`data-lingua-*` attributes):**

| Attribute              | Element                                |
|------------------------|----------------------------------------|
| `data-lingua-selector` | Root `<nav>` element                   |
| `data-lingua-list`     | The `<ul>` language list               |
| `data-lingua-item`     | Each `<li>` language entry             |
| `data-lingua-active`   | The `<li>` of the active language      |
| `data-lingua-button`   | The `<button>` inside each `<li>`      |
| `data-lingua-name`     | Language English display name `<span>` |
| `data-lingua-native`   | Language native name `<span>`          |
| `data-lingua-code`     | Language ISO code `<span>`             |

**Plain CSS example:**

```css
[data-lingua-selector] {
    display: flex;
    gap: 0.5rem;
}

[data-lingua-item] {
    cursor: pointer;
}

[data-lingua-active] {
    font-weight: bold;
}
```

**Tailwind CSS example:**

```blade
<livewire:lingua::headless-language-selector>
    <x-slot:item="language">
        <span class="px-3 py-1 rounded hover:bg-gray-100">
            {{ $language->native }}
        </span>
    </x-slot>
</livewire:lingua::headless-language-selector>
```

---

## 💎 Lingua Facade

Lingua ships a static `Lingua` facade that gives you programmatic access to language and translation data from anywhere
in your application.

```php
use Rivalex\Lingua\Facades\Lingua;
```

### Locale helpers

```php
// Current application locale (mirrors app()->getLocale())
Lingua::getLocale();                // 'en'

// Locale marked as default in the database
Lingua::getDefaultLocale();        // 'en'

// English display name for a locale
Lingua::getLocaleName();           // 'English'  - uses current locale
Lingua::getLocaleName('fr');       // 'French'

// Native name
Lingua::getLocaleNative();         // 'English'
Lingua::getLocaleNative('ar');     // 'العربية'

// Text direction
Lingua::getDirection();            // 'ltr'
Lingua::getDirection('ar');        // 'rtl'

// Full locale registry metadata (code, name, native, direction, regional, type)
$info = Lingua::info('fr');        // LocaleInfo|null
```

### Availability checks

```php
// All known locale codes
Lingua::available();               // ['en', 'fr', 'de', ...]

// Installed locale codes
Lingua::installed();               // ['en', 'fr']

// Available but not yet installed
Lingua::notInstalled();            // ['de', 'es', ...]

// Check a specific locale
Lingua::isInstalled('fr');         // true / false
Lingua::isAvailable('de');         // true if in registry
Lingua::isAvailable('xx');         // false if unknown locale code
Lingua::hasLocale('en-US');        // true if found by code or regional match

// Check whether a locale is the default
Lingua::isDefaultLocale();         // true  - uses current app locale
Lingua::isDefaultLocale('fr');     // false

// Change the default locale (persisted in the database)
Lingua::setDefaultLocale('fr');
```

### Language models

```php
// All installed languages (Eloquent Collection)
Lingua::languages();

// All languages with completion statistics
// Each model includes: total_strings, translated_strings, missing_strings, completion_percentage
Lingua::languagesWithStatistics();

// Single language by locale code
Lingua::get('fr');                 // Language|null

// Default and fallback language models
Lingua::getDefault();              // Language|null
Lingua::getFallback();             // Language|null
```

### Translation statistics

```php
// Stats for a specific locale (or current locale if omitted)
Lingua::getLocaleStats('fr');
// [
//   'total'      => 1240,
//   'translated' => 980,
//   'missing'    => 260,
//   'percentage' => 79.03,
// ]
```

### Reading translations

```php
// Keys are given as group.key (or vendor::group.key, or single.<sentence>).
// A bare key is accepted only when exactly one translation has it.

// All locale variants for a key
Lingua::getTranslations('messages.welcome');
// ['en' => 'Welcome', 'fr' => 'Bienvenue', 'de' => 'Willkommen']

// Single locale value (empty string if missing)
Lingua::getTranslation('messages.welcome');           // uses current locale
Lingua::getTranslation('messages.welcome', 'fr');     // 'Bienvenue'

// All translations for a group, optionally filtered to a locale
Lingua::getTranslationByGroup('validation');
Lingua::getTranslationByGroup('validation', 'fr');

// Raw Eloquent collection
Lingua::translations();
```

### Writing & deleting translations

```php
// Update an existing translation value (nothing happens when the key is not found; uses current locale if omitted)
Lingua::setTranslation('messages.welcome', 'Bienvenue', 'fr');
Lingua::setTranslation('messages.welcome', 'Welcome');          // current locale

// Remove a locale's value from a translation key
Lingua::forgetTranslation('messages.welcome', 'fr');
Lingua::forgetTranslation('messages.welcome');                  // current locale
```

### Vendor translations

```php
// All vendor translations for a package and locale
Lingua::getVendorTranslations('my-package', 'fr');

// Set a vendor translation
Lingua::setVendorTranslation('my-package', 'messages', 'key', 'value', 'fr');
```

### Language lifecycle

```php
// Install a language: creates DB record, seeds bundled translations
// (DB-native - does not use laravel-lang)
Lingua::addLanguage('fr');

// Remove a language: deletes record and cleans up storage
Lingua::removeLanguage('fr');

// Install bundled translations for the default language (file mode)
Lingua::installDefaultLanguage();
```

> **Note:** `addLanguage()` and `removeLanguage()` manage the Language record and storage. Use the Artisan commands
`lingua:add` / `lingua:remove` for the fully orchestrated operation (record + storage + cache invalidation).

### Sync

```php
// Import all local lang/ files into the database
Lingua::syncToDatabase();

// Write all database translations into the local lang/ files
// (merge: keys that exist only in the files are kept)
Lingua::syncToLocal();

// Re-sync translations for all installed locales
Lingua::updateLanguages();
```

> **Note:** `Lingua::optimize()` is deprecated - surgical cache invalidation per `(locale, group)` pair makes it
> unnecessary.

---

## 🏗 Architecture

### How translations are stored

In **database mode**, Lingua stores translations in the `language_lines` table. Each row holds all locales in a single
JSON `text` column, eliminating the need for per-locale rows:

```
group       | key          | text
------------|--------------|--------------------------------------------------------------
validation  | required     | {"en": "The :attribute field is required.", "it": "..."}
single      | Welcome      | {"en": "Welcome", "fr": "Bienvenue", "de": "Willkommen"}
```

This design allows instant locale switching at runtime without additional queries per language.

### Storage abstraction

Lingua defines a `TranslationRepository` contract with two implementations:

| Implementation       | Driver     | Description                                                                           |
|----------------------|------------|---------------------------------------------------------------------------------------|
| `DatabaseRepository` | `database` | Reads/writes `language_lines`; supports HTML/Markdown types, vendor guard, statistics |
| `FileRepository`     | `file`     | Reads/writes lang/ PHP/JSON files; types flattened to plain text                      |

### Bundled translation dataset

Lingua ships a bundled dataset of **5 902 strings** across **26 locales** and **7 translation groups** (auth,
pagination, passwords, validation, http-statuses, errors, notifications), aligned to **Laravel v13.14.0**. The dataset
is read by `BundledTranslationSource` at locale install-time and merged into the database or lang/ files during
`syncToDatabase()` and `installLocale()`. Notification strings are projected into `lang/{locale}.json` by
`NotificationProjector`.

**Bundled locales:** Arabic, German, English, Spanish, Persian, French, Hebrew, Hindi, Indonesian, Italian, Japanese,
Korean, Malay, Norwegian Bokmål, Dutch, Polish, Portuguese, Brazilian Portuguese, Romanian, Russian, Swedish, Thai,
Turkish, Ukrainian, Vietnamese, Chinese (Simplified).

This replaces the need for `laravel-lang` or any external language package.

### Translation loading at runtime

`LinguaManager` extends Laravel's built-in `FileLoader` and is registered via `extend()` on the `translation.loader`
binding. At runtime it merges:

1. File-based translations from `lang/` (via `FileLoader`)
2. Database translations via the `Db` loader (`Rivalex\Lingua\Database\Db`)

Database translations take precedence over file translations, JSON strings (`lang/en.json`, stored as `single` rows)
included. In file mode the database is never read at runtime. All translations are cached per `(locale, group)` pair via
`Cache::rememberForever`. Cache is invalidated surgically on model save/delete - no full-cache flush required.

The cache is an optimisation, the database stays the source of truth: if the cache store is unavailable (for example a
Redis timeout), reads and the write-back after a miss fall back to the database, the exception is reported, and a
request-scoped circuit breaker skips the store for the rest of the request. Invalidation is deliberately not guarded: a
failed forget surfaces instead of leaving a stale entry behind.

### Translation types

Each string is classified automatically during sync:

| Type       | Use case                        | Auto-detected when…       |
|------------|---------------------------------|---------------------------|
| `text`     | Plain strings, labels, messages | Default                   |
| `html`     | Rich content with HTML markup   | String contains HTML tags |
| `markdown` | Markdown-formatted content      | String parses as Markdown |

The type drives which editor is shown in the Translations UI. Types are preserved in database mode; flattened to `text`
in file mode.

#### Rendering translations safely

Lingua checks markup when a value is written from the UI, an import or the facade:

- `html` values always go through the sanitizer.
- `text` and `markdown` values that contain disallowed HTML (a `<script>` element, an event-handler attribute, an
  unlisted tag, a truncated tag or a sequence such as `a<b`) are rejected with a validation error, an import row error
  or an exception, not rewritten. Values without markup are stored byte for byte. Write `a < b` (with spaces) instead of
  `a<b`.
- A value for `Pagination Navigation`, `pagination.previous` or `pagination.next` that contains `"`, `<` or `>` is
  rejected, because Laravel's pagination views print these keys inside an HTML attribute.
- In `html` values, `<script>` elements and event-handler attributes are removed. Protocol-relative and backslash URIs
  are removed too.
- A link built from a placeholder needs a fixed prefix: `<a href=":url">` is removed from an `html` value and makes a
  `text` or `markdown` value rejected, while `href="/:path"` and `href="https://example.com/:path"` keep working.
- Markdown autolinks (`<https://...>`) are not accepted: write `[text](url)` instead.
- Markup in `text` and `markdown` values is checked by Lingua itself, whatever the libxml version of your PHP build.
  These forms are rejected: HTML comments, unbalanced or mismatched tags, an attribute repeated on one tag, tags that
  are not on the allow-list, and a URI in `href` or `src` that hides a character behind a numeric reference without
  the closing `;` (`&#106avascript:`).
- The sanitizer is an allow-list: `script` and `style` elements are removed with their content, other tags that are not
  allowed are unwrapped (their text is kept), every attribute is dropped except `href` and `title` on `a` and `src`,
  `alt`, `title`, `width` and `height` on `img` (so `class`, `style`, `target` and `rel` are removed), and URIs are kept
  only when they are relative or use `http`, `https` or `mailto`.
- Files synchronized from your own `lang/` directory are trusted and left untouched.

Render each type accordingly:

```blade
{{-- text --}}
{{ __('messages.title') }}

{{-- html: sanitized on write --}}
{!! __('messages.intro') !!}

{{-- markdown: the host renders it --}}
{!! Str::markdown(__('messages.body'), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
```

Replacement values (for example `__('key', ['name' => $value])`) are not sanitized, and Laravel does not escape them.
When the translation is printed with `{!! !!}`, escape each value with `e()`. A value placed in a `href` or `src`
attribute must also be validated by your application, because `e()` does not block `javascript:`.

#### Group and key rules

Group and vendor names must match `^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$` (the group may also be the literal `single`).
Keys must be non-empty, at most 300 characters, without leading, trailing or repeated whitespace, free of control
characters and free of any dot segment that is exactly
`*` (a wildcard for every group). Outside `single`, keys must also be free of `*` and of empty dot segments. The rules
apply to writes from the UI, import and the facade; files synchronized from your own `lang/` directory are not
validated.

A key may not contain `|`. A new key may not be a dot-prefix of an existing key in the same group (`auth` when
`auth.failed` exists), nor have an existing key as its dot-prefix. A new group may not equal an existing group ignoring
case. In file mode, `single` keys are literal and are not split on dots.

### Bi-directional sync

```
lang/en/*.php       ─┐
lang/en.json         │  lingua:sync-to-database →  language_lines (DB)
lang/it/*.php        │
lang/it.json        ─┤
lang/vendor/…        │  ← lingua:sync-to-local
                    ─┘
```

- **`sync-to-database`** - reads every locale file (core + vendor packages) and upserts rows in `language_lines`,
  auto-creating `languages` records for any new locales discovered. The default locale is processed first and its keys
  form the reference set.
- **`sync-to-local`** - reads every row in `language_lines` and merges the values into the locale-specific PHP/JSON
  files in `lang/`, including vendor subdirectories. It merges: the files keep their shape and the keys that exist only
  in the files are kept. A key you delete in the UI stays in the files until you delete it there yourself.

### Locale middleware

`LinguaMiddleware` is automatically appended to the `web` middleware group on boot. It:

1. Reads the active locale from the session (`lingua.session_variable`)
2. Falls back to the database default language
3. Calls `app()->setLocale()` and conditionally writes the locale to session (avoids dirty session on every request)

### Authorization

Admin routes carry `can:{gate}` (default ability `manage-lingua`) unless `LINGUA_GATE` is empty, and every admin
Livewire component checks the same ability itself. Define the ability in `AppServiceProvider`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('manage-lingua', fn (User $user) => $user->is_admin);
```

Until it is defined, access is allowed only in the `local` environment and answers `403` elsewhere. Use `LINGUA_GATE`
to pick another ability name, or set it empty to rely on authentication only. See
[Securing the admin UI](#3-securing-the-admin-ui).

Other safeguards: the default language cannot be deleted (UI and `Lingua::removeLanguage()` refuse), and deleting a
language leaves vendor rows alone and runs in a transaction in database mode.

---

## 💎 Lingua Pro

Lingua Pro is an optional add-on that extends the base package with premium features:

- **AI-powered translation** - DeepL, Anthropic, and Google Translate integrations
- **Translation memory** - avoid re-translating identical or similar strings
- **Glossary** - enforce consistent terminology across your project
- **Usage analytics** - track which strings are most accessed

Requires a Lingua Pro license. See [lingua.rivalex.dev](https://lingua.rivalex.dev) for details.

To suppress the upgrade nudge in the admin UI (e.g. when Lingua Pro is installed):

```ini
# .env
LINGUA_SUPPRESS_PRO_NUDGE=true
```

---

## 🧪 Testing

```bash
# Run the full test suite
composer test

# Run with coverage report
composer test-coverage
```

The suite uses [Pest](https://pestphp.com) v4 and covers:

- All Artisan commands - happy paths, driver-aware behavior, and error handling
- All Livewire components - rendering, interactions, and event dispatching
- Bi-directional sync operations
- Import and export (all formats, all scopes, dry-run and commit flows)
- All Blade components
- Helper functions
- The Lingua facade

1,000+ tests total (2 skipped without `openspout/openspout`: XLSX and ODS integration).

---

## 🤝 Contributing

Contributions are welcome! Please open an issue first to discuss your proposed change, then submit a PR. Run
`composer lint` before pushing.

---

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

---

<div align="center">

Built with ❤️ by [Alessandro Rivolta](https://github.com/rivalex)

Powered by [Laravel](https://laravel.com) · [Livewire](https://livewire.laravel.com) · [Flux](https://fluxui.dev)

</div>
