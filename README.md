<div align="center">

# 🌍 Lingua

**The complete multilingual management system for Laravel**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rivalex/lingua.svg?style=flat-square)](https://packagist.org/packages/rivalex/lingua)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-orange?style=flat-square)](https://laravel.com)
[![License](https://img.shields.io/github/license/rivalex/lingua?style=flat-square)](LICENSE.md)
[![codecov](https://codecov.io/github/rivalex/lingua/branch/main/graph/badge.svg?token=9RKRB8AYD6)](https://codecov.io/github/rivalex/lingua)
[![Tests](https://github.com/rivalex/lingua/actions/workflows/run-tests.yml/badge.svg)](https://github.com/rivalex/lingua/actions/workflows/run-tests.yml)

Lingua brings **database-driven translations** to Laravel with a beautiful Livewire + Flux UI — install languages, manage translations, and sync everything with a single command.

[Features](#-features) · [Installation](#-installation) · [Configuration](#-configuration) · [Artisan Commands](#-artisan-commands) · [UI Guide](#-ui-guide) · [Architecture](#-architecture)

</div>

---

## ✨ Features

| Feature | Description                                                                                      |
|---|--------------------------------------------------------------------------------------------------|
| **Database-backed translations** | All translations stored in the database, editable instantly without deployments                  |
| **Livewire UI** | Reactive, real-time language and translation management interface                                |
| **Flux UI components** | Modern, accessible UI built with Livewire Flux                                                   |
| **Bi-directional sync** | Push translations to the database or pull them back to local PHP/JSON files                      |
| **Laravel Lang integration** | Install 70+ languages with one command, auto-updated via `laravel-lang`                          |
| **Rich text support** | Translations can be plain text, HTML, or Markdown                                                |
| **Language selector** | Configurable sidebar, dropdown, or modal language switcher for users                             |
| **Progress tracking** | Per-language completion percentage and missing-translation counts                                |
| **RTL support** | First-class right-to-left language handling                                                      |
| **Vendor translations** | Manage package translations alongside your own                                                   |
| **Database-agnostic** | Full support for SQLite, MySQL, PostgreSQL, and SQL Server                                       |
| **Fully tested** | 150+ tests with Pest, covering commands, Livewire components, Blade components and helpers class |

---

## 📦 Requirements

- PHP **8.4+**
- Laravel **12+**
- Livewire **4.1+**
- Livewire Flux **2.12+**

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
- Publish the configuration file to `config/lingua.php`
- Publish and run the database migrations
- Seed the database with your default language and its translations
- Optionally star the repo on GitHub ⭐

That's it — Lingua is ready.

### 3. Access the UI

| Page | URL |
|---|---|
| Languages | `your-app.test/lingua/languages` |
| Translations | `your-app.test/lingua/translations/{locale?}` |

---

## ⚙️ Configuration

After installation, `config/lingua.php` gives you full control:

```php
return [
    // Directory where local language files are stored
    'lang_dir' => lang_path(),

    // Application default locale
    'default_locale' => config('app.locale', 'en'),

    // Fallback locale when a translation is missing
    'fallback_locale' => config('app.fallback_locale', 'en'),

    // Middleware applied to Lingua's routes
    'middleware' => ['web'],

    // URL prefix for Lingua's management pages
    'routes_prefix' => 'lingua',

    // Session key used to store the active locale
    'session_variable' => 'locale',

    // Language selector widget settings
    'selector' => [
        'mode'       => 'sidebar',   // 'sidebar' | 'modal' | 'dropdown'
        'show_flags' => true,
    ],

    // Rich-text editor toolbar options
    'editor' => [
        'headings'      => false,
        'bold'          => true,
        'italic'        => true,
        'underline'     => true,
        'strikethrough' => false,
        'bullet'        => true,
        'ordered'       => true,
        'clear'         => true,
        // ... more options available
    ],
];
```

---

## 🛠 Artisan Commands

Lingua ships with a complete command suite for terminal-driven language and translation management.

### Language management

| Command | Description |
|---|---|
| `lingua:add {locale}` | Install a new language: downloads files, creates DB record, syncs translations |
| `lingua:remove {locale}` | Remove a language: deletes files, cleans DB, reorders remaining languages |
| `lingua:update-lang` | Update all installed language files via Laravel Lang, then re-sync to database |

```bash
# Add Italian
php artisan lingua:add it

# Add Brazilian Portuguese
php artisan lingua:add pt_BR

# Remove French (the default language is protected)
php artisan lingua:remove fr

# Pull the latest translation strings from Laravel Lang and sync to DB
php artisan lingua:update-lang
```

### Translation sync

| Command | Description |
|---|---|
| `lingua:sync-to-database` | Import all local PHP/JSON translation files into the database |
| `lingua:sync-to-local` | Export all database translations back to local PHP/JSON files |

```bash
# Populate the database from existing lang/ files (e.g. after a fresh install)
php artisan lingua:sync-to-database

# Write database translations to lang/ files (e.g. for version control or deployment)
php artisan lingua:sync-to-local
```

### Install command

```bash
# Interactive first-time setup wizard
php artisan lingua:install
```

---

## 🖥 UI Guide

### Languages page — `/lingua/languages`

The languages page is your control center for installed locales.

**Available actions:**
- **Add a language** — choose from 70+ locales; files are installed and translations synced automatically
- **Remove a language** — confirmation modal prevents accidental deletion; the default language is protected
- **Set the default language** — one click sets the new application default
- **Reorder languages** — drag-and-drop to control display order across the UI
- **Sync to database** — import all local `lang/` files into the database
- **Sync to local** — export database translations back to `lang/` files
- **Update via Laravel Lang** — pull the latest strings from upstream `laravel-lang` packages

Each language row shows the **completion percentage** and a count of **missing translations** so you can prioritise your translation effort.

### Translations page — `/lingua/translations/{locale?}`

Manage individual translation strings with a filterable, paginated table.

**Available actions:**
- **Search** by key, group, or value
- **Filter** by locale, group, or translation type (text / HTML / Markdown)
- **Show only missing** translations for a locale to focus your translation work
- **Create** new custom translation entries
- **Edit** any string inline — the rich-text editor activates automatically for HTML and Markdown types
- **Delete** translations globally or for a specific locale only
- **Copy** the translation key to clipboard with one click

### Language selector component

Embed a language switcher anywhere in your Blade layouts:

```blade
<livewire:lingua::language-selector />
```

Control the display mode via config or inline props:

```blade
{{-- sidebar (default), dropdown, or modal --}}
<livewire:lingua::language-selector mode="dropdown" :show-flags="false" />
```

---

## 🏗 Architecture

### How translations are stored

Lingua stores translations in the `language_lines` table, extending Spatie's [laravel-translation-loader](https://github.com/spatie/laravel-translation-loader). Each row holds **all locales in a single JSON `text` column**, eliminating the need for per-locale rows:

```
group       | key          | text
------------|--------------|--------------------------------------------------------------
validation  | required     | {"en": "The :attribute field is required.", "it": "..."}
single      | Welcome      | {"en": "Welcome", "fr": "Bienvenue", "de": "Willkommen"}
```

This design allows instant locale switching at runtime without additional queries per language.

### Translation types

Each string is classified automatically during sync:

| Type | Use case | Auto-detected when… |
|---|---|---|
| `text` | Plain strings, labels, messages | Default |
| `html` | Rich content with HTML markup | String contains HTML tags |
| `markdown` | Markdown-formatted content | String parses as Markdown |

The type drives which editor is shown in the Translations UI.

### Bi-directional sync

```
lang/en/*.php       ─┐
lang/en.json         │  lingua:sync-to-database →  language_lines (DB)
lang/it/*.php        │
lang/it.json        ─┤
lang/vendor/…        │  ← lingua:sync-to-local
                    ─┘
```

- **`sync-to-database`** — reads every locale file (core + vendor packages) and upserts rows in `language_lines`, auto-creating `languages` records for any new locales discovered.
- **`sync-to-local`** — reads every row in `language_lines` and writes locale-specific PHP/JSON files back to `lang/`, including vendor subdirectories.

### Translation loading at runtime

Lingua registers a custom `LinguaManager` as the Laravel translation loader. At runtime it merges:
1. File-based translations from `lang/`
2. Database translations via Spatie's `Db` loader

Database translations take precedence, enabling live overrides without touching source files.

### Locale middleware

`LinguaMiddleware` is automatically appended to the `web` middleware group on boot. It:
1. Reads the active locale from the session (`lingua.session_variable`)
2. Falls back to the database default language
3. Calls `app()->setLocale()` and stores the locale in the session for the next request

---

## 🧪 Testing

```bash
# Run the full test suite
composer test

# Run with coverage report
composer test-coverage
```

The suite uses [Pest](https://pestphp.com) and covers:
- All 5 Artisan commands — happy paths and error handling
- All Livewire components — rendering, interactions, and event dispatching
- Bi-directional sync operations
- All blade components
- Helper functions

---

## 🤝 Contributing

Contributions are welcome! Please open an issue first to discuss your proposed change, then submit a PR. Run `composer lint` before pushing.

---

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

---

<div align="center">

Built with ❤️ by [Alessandro Rivolta](https://github.com/rivalex)

Powered by [Laravel](https://laravel.com) · [Livewire](https://livewire.laravel.com) · [Flux](https://fluxui.dev) · [Laravel Lang](https://laravel-lang.com) · [Spatie](https://spatie.be)

</div>
