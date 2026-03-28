# Changelog

All notable changes to `lingua` will be documented in this file.

## Rivalex Lingua - 2026-03-28

### 2026-03-28

#### Added

- **Laravel 13 compatibility** — `illuminate/contracts ^13.0` and `orchestra/testbench ^11.0` confirmed; no breaking-change impact from the framework. Livewire 4.x is fully compatible with Laravel 13.
- README and documentation updated to reflect supported range: Laravel **11 | 12 | 13**.

---

## Rivalex Lingua - 2026-03-27

All notable changes to `lingua` will be documented in this file.

### 2026-03-26

#### Fixed

- **`Lingua::isDefaultLocale()`** — missing null-safe operator caused a `TypeError` when called with a locale code that has no matching record in the database; now returns `false` safely.
- **`LinguaServiceProvider::registerTranslator()`** — `Language::default()->code` replaced with `Language::default()?->code` to avoid `TypeError` during bootstrap when the `languages` table is empty or not yet migrated.
- **`LinguaMiddleware`** — same nullsafe fix: `Language::default()->code` → `Language::default()?->code`.
- **`Translation\Delete::mount()`** — accessing `->name` on the result of `Language::first()` without a null guard caused a `TypeError` when the locale was absent from the database; now falls back to the locale code string.
- **`LanguageSelector::changeLocale()`** — the method accepted any arbitrary string passed as `$locale` and stored it directly in the session without validating it against the installed languages, allowing an attacker to inject arbitrary locale codes. It now silently returns early if the locale is not found in the database.
- **`Language::setDefault()`** — the two separate UPDATE queries ran outside a transaction, leaving a window where no language was marked as default. Both queries are now wrapped in `DB::transaction()`.
- **`Language\Create`** — misleading log message "Languages reorder failed" corrected to "Add language failed".

#### Added

- **`Lingua::addLanguage(string $locale)`** — facade method (and docblock) for installing language files via `lang:add`.
- **`Lingua::removeLanguage(string $locale)`** — new facade method for removing language files via `lang:rm --force`; mirrors the file-management step of `lingua:remove`.
- `@method` docblocks for `addLanguage()` and `removeLanguage()` in the `Lingua` facade class.
- Class-level docblock example block **"Language lifecycle"** added to the `Lingua` facade.
- README: **"Language lifecycle"** section under the Lingua Facade documenting `addLanguage()` and `removeLanguage()` with a note distinguishing them from the full `lingua:add` / `lingua:remove` Artisan commands.
- Feature tests: `addLanguage` and `removeLanguage` smoke tests added to `LinguaFacadeTest`.

#### Changed

- `Language/Delete` Livewire component: replaced direct `Artisan::call('lang:rm …')` call with `Lingua::removeLanguage()` so the component goes through the facade consistently.


---

### Previously unreleased (now merged)

#### Added

- **`Lingua` facade** fully implemented with a complete API surface:
  - Locale helpers: `getLocale()`, `getDefaultLocale()`, `hasLocale()`, `isDefaultLocale()`, `setDefaultLocale()`
  - Language metadata: `getLocaleName()`, `getLocaleNative()`, `getDirection()`
  - Language queries: `languages()`, `languagesWithStatistics()`
  - Translation reads: `translations()`, `getTranslation()`, `getTranslations()`, `getTranslationByGroup()`, `getLocaleStats()`
  - Translation writes: `setTranslation()`, `forgetTranslation()`
  - Sync helpers: `syncToDatabase()`, `syncToLocal()`
  - Vendor translation helpers: `getVendorTranslations()`, `setVendorTranslation()`
  
- **`VendorTranslationProtectedException`** — thrown when attempting to delete a vendor-owned translation.
- **Vendor translation protection** — vendor translations cannot be deleted from the UI; attempting to do so dispatches a `vendor_translation_protected` event and closes the modal instead.
- **Vendor translation locking in `Update`** — when editing a vendor translation, `group` and `key` fields are locked; only the text value and type may be changed.
- `isVendor` property exposed on the `Translation/Update` Livewire component for view-layer awareness.
- Feature tests: `LinguaFacadeTest` and `VendorTranslationTest` covering the full facade API and vendor-protection behaviour.
- Helper unit tests extended to cover new utility cases.

#### Changed

- `Translation/Update`: vendor translations skip the group/key update path and only persist `type` and text changes.
- `Translation/Delete`: vendor translations are intercepted before deletion and trigger a protected event instead.
- `LinguaServiceProvider`: updated to register the vendor protection exception and related bindings.

## 2026-03-26

### Fixed

- **`Lingua::isDefaultLocale()`** — missing null-safe operator caused a `TypeError` when called with a locale code that has no matching record in the database; now returns `false` safely.
- **`LinguaServiceProvider::registerTranslator()`** — `Language::default()->code` replaced with `Language::default()?->code` to avoid `TypeError` during bootstrap when the `languages` table is empty or not yet migrated.
- **`LinguaMiddleware`** — same nullsafe fix: `Language::default()->code` → `Language::default()?->code`.
- **`Translation\Delete::mount()`** — accessing `->name` on the result of `Language::first()` without a null guard caused a `TypeError` when the locale was absent from the database; now falls back to the locale code string.
- **`LanguageSelector::changeLocale()`** — the method accepted any arbitrary string passed as `$locale` and stored it directly in the session without validating it against the installed languages, allowing an attacker to inject arbitrary locale codes. It now silently returns early if the locale is not found in the database.
- **`Language::setDefault()`** — the two separate UPDATE queries ran outside a transaction, leaving a window where no language was marked as default. Both queries are now wrapped in `DB::transaction()`.
- **`Language\Create`** — misleading log message "Languages reorder failed" corrected to "Add language failed".

### Added

- **`Lingua::addLanguage(string $locale)`** — facade method (and docblock) for installing language files via `lang:add`.
- **`Lingua::removeLanguage(string $locale)`** — new facade method for removing language files via `lang:rm --force`; mirrors the file-management step of `lingua:remove`.
- `@method` docblocks for `addLanguage()` and `removeLanguage()` in the `Lingua` facade class.
- Class-level docblock example block **"Language lifecycle"** added to the `Lingua` facade.
- README: **"Language lifecycle"** section under the Lingua Facade documenting `addLanguage()` and `removeLanguage()` with a note distinguishing them from the full `lingua:add` / `lingua:remove` Artisan commands.
- Feature tests: `addLanguage` and `removeLanguage` smoke tests added to `LinguaFacadeTest`.

### Changed

- `Language/Delete` Livewire component: replaced direct `Artisan::call('lang:rm …')` call with `Lingua::removeLanguage()` so the component goes through the facade consistently.


---

## Previously unreleased (now merged)

### Added

- **`Lingua` facade** fully implemented with a complete API surface:
  - Locale helpers: `getLocale()`, `getDefaultLocale()`, `hasLocale()`, `isDefaultLocale()`, `setDefaultLocale()`
  - Language metadata: `getLocaleName()`, `getLocaleNative()`, `getDirection()`
  - Language queries: `languages()`, `languagesWithStatistics()`
  - Translation reads: `translations()`, `getTranslation()`, `getTranslations()`, `getTranslationByGroup()`, `getLocaleStats()`
  - Translation writes: `setTranslation()`, `forgetTranslation()`
  - Sync helpers: `syncToDatabase()`, `syncToLocal()`
  - Vendor translation helpers: `getVendorTranslations()`, `setVendorTranslation()`
  
- **`VendorTranslationProtectedException`** — thrown when attempting to delete a vendor-owned translation.
- **Vendor translation protection** — vendor translations cannot be deleted from the UI; attempting to do so dispatches a `vendor_translation_protected` event and closes the modal instead.
- **Vendor translation locking in `Update`** — when editing a vendor translation, `group` and `key` fields are locked; only the text value and type may be changed.
- `isVendor` property exposed on the `Translation/Update` Livewire component for view-layer awareness.
- Feature tests: `LinguaFacadeTest` and `VendorTranslationTest` covering the full facade API and vendor-protection behaviour.
- Helper unit tests extended to cover new utility cases.

### Changed

- `Translation/Update`: vendor translations skip the group/key update path and only persist `type` and text changes.
- `Translation/Delete`: vendor translations are intercepted before deletion and trigger a protected event instead.
- `LinguaServiceProvider`: updated to register the vendor protection exception and related bindings.


---

## 2026-03-19

### Added

- Translations for Lingua's own UI strings in six additional locales: `es`, `fr`, `it`, `ja`, `pt`, `ru`, `zh_CN`.

### Changed

- **Translation placeholders** simplified across `translation/create`, `translation/row`, and `translation/update` views.
- **Livewire components** (`LanguageSelector`, `Selector/Icon`) made more flexible with improved prop/slot handling.
- `LinguaType` enum updated to align with the simplified placeholder approach.
- `language/row` and `selector/*` Blade views updated for consistency.
- `editor` Blade component cleaned up.
- `language-flag` component updated.


---

## 2026-03-18

### Added

- **`AddLangCommand`** — artisan command to add a new language to the application.
- **`RemoveLangCommand`** and **`UpdateLangCommand`** registered in `LinguaServiceProvider` (commands existed previously but were not registered).
- README: "Publishing" section with detailed instructions for publishing config, views, migrations, language files, and assets.
- Comprehensive feature tests for all artisan commands: `AddLangCommandTest`, `RemoveLangCommandTest`, `UpdateLangCommandTest`, `SyncToDatabaseCommandTest`, `SyncToLocalCommandTest`.
- Feature tests for all Livewire translation components: `CreateTest`, `DeleteTest`, `RowTest`, `UpdateTest`.
- Feature tests for Blade components: `BladeComponentsTest`.
- Expanded `TranslationsTest` coverage.

### Changed

- CI: GitHub Actions test job timeout increased to 120 minutes to prevent premature failures on longer runs.
- README: Removed `style=flat-square` from badge URLs.

### Dependencies

- `laravel-lang/common` bumped from `6.7.2` to `6.8.0`.
- `laravel/pint` bumped from `1.28.0` to `1.29.0`.


---

## 2026-03-11 — Initial Release

### Added

- **Language management UI** — Livewire-powered CRUD for application languages (create, delete, set default, reorder).
- **Translation management UI** — Livewire-powered interface for browsing and editing translations per locale, including rich-text (TipTap) and plain-text editor modes.
- **Language selector** — embeddable Livewire component in three styles: `dropdown`, `modal`, and `sidebar`.
- **Artisan commands**:
  - `lingua:sync-to-database` — import local translation files into the database.
  - `lingua:sync-to-local` — export database translations back to local files.
  - `lingua:update-lang` — update language files via `laravel-lang`.
  
- **`LinguaMiddleware`** — sets the active locale from the authenticated user's language preference.
- **`LinguaSeeder`** — seeds the database with language records.
- **Database migration** — creates the `lingua_languages` and `lingua_translations` tables.
- **`Language` and `Translation` Eloquent models** with factory support.
- **`Lingua` facade** stub (fully implemented in Unreleased above).
- **`LinguaType` enum** for translation content types (plain, HTML, Markdown).
- **Blade components**: `clipboard`, `editor`, `language-flag`.
- **Publishable assets**: config, views, migrations, language files (`en` baseline), compiled CSS/JS.
- **Frontend assets**: TipTap-based rich-text editor, autocomplete, flag icons via `outhebox/blade-flags`, Livewire Flux UI.
- CI workflows: test runner, PHP code-style fixer (Pint), Dependabot auto-merge, changelog updater.
- Dependabot enabled for Composer and GitHub Actions dependencies.
- `actions/checkout` bumped from `5` to `6`.
- Codecov integration in the test workflow.
- PHPStan baseline configuration.
