# Changelog

All notable changes to `lingua` will be documented in this file.

## Lingua v2.0.1 - 2026-07-18

### Fixed

- **`group_key` NOT NULL violation on seed (PostgreSQL)** — `Translation::create()`/`updateOrCreate()` could omit `group_key` from the INSERT, crashing `migrate:fresh --seed` (and any `Lingua::addLanguage()` call) with `SQLSTATE[23502]` on PostgreSQL. Root cause: the model populated `group_key` through two overlapping mechanisms (a dirty-gated `creating`/`saving` pair, plus a `groupKey()` Attribute `set:` closure) that could drift out of sync. Consolidated into a single unconditional `saving()` hook — `group_key` is now recomputed from `group`/`key`/`is_vendor`/`vendor` on every persist, insert or update, regardless of dirty state. Added end-to-end regression coverage (`tests/Feature/Models/TranslationGroupKeyTest.php`) driving the full `Lingua::addLanguage()` → `syncToDatabase()` path and asserting no row is ever left with a null/empty `group_key`.

## Lingua v2.0.0 - 2026-06-30

### Lingua 2.0.0 - 2026-06-29

#### Breaking Changes

- **Removed `spatie/laravel-translation-loader`** — `Translation` extends `Illuminate\Database\Eloquent\Model` directly. Custom loaders must implement `Rivalex\Lingua\Contracts\TranslationLoader` (method signature `loadTranslations(string $locale, string $group, ?string $namespace = null): array` unchanged).
- **Removed `laravel-lang/common`** — locale metadata served by the internal `LocaleRegistry`. `lingua:add` is DB-native (no `lang:add` call). `lingua:update-lang` no longer downloads files from laravel-lang. Translations provisioned via the bundled dataset.
- **`Lingua::info()` return type** — now returns `?Rivalex\Lingua\Locales\LocaleInfo` (was `LaravelLang\LocaleData`). Property access changed: `->locale->name`→`->name`, `->localized`→`->name`, `->direction->value`→`->direction`, others unchanged. Returns `null` for unknown locales (previously threw).
- **`addLanguage()` / `removeLanguage()` DB-native only** — no filesystem writes. Use `lingua:add` / `lingua:remove` Artisan commands for fully orchestrated operations (files + DB + sync).
- **Route middleware default → `['web', 'auth']`** — admin routes now require authentication by default. Hosts that relied on unauthenticated access must set `'middleware' => ['web']` in `config/lingua.php`. Existing published configs are unaffected.
- **`navigate` default → `false`** — locale switches and page transitions use full-page redirects. Opt back in with `'navigate' => true`.
- **PHP requirement → 8.3+** (was 8.1+). Laravel 11, 12, 13 supported.

#### Security

- **HtmlSanitizer** — DOM-based whitelist sanitizer replaces `strip_tags()` in HTML translation preview (`translation/row.blade.php`). `strip_tags()` preserved event handlers and `javascript:` URIs on allowed elements. `HtmlSanitizer::sanitize()` allows only a curated per-tag attribute whitelist; URI attributes validated against `http`/`https`/`mailto` schemes.
- **PathGuard** (`F3`) — LFI/RCE prevention on all filesystem write sinks. `BundledTranslationSource::translationsFor()` and `DatabaseRepository::installLocale()` call `PathGuard::assertSafeSegment()` as first statement.
- **Auth gate** (`F9`) — `'gate' => env('LINGUA_GATE', null)` adds `can:{gate}` middleware to all admin routes for role-based access control. Default `null` — no breaking change for existing installs.
- **Cache DoS** (`F1`) — `Translation::getTranslationsForGroup()` / `getVendorTranslationsForGroup()` skip `rememberForever` for locales that fail the canonical format regex; closes unbounded cache growth.
- **Multi-DB JSON-SQL ban** (`F2`) — `DatabaseRepository::paginate()` (onlyMissing), `byGroup()`, `vendor()`, and `RemoveLangCommand` replaced JSON arrow operators (`text->>$locale`, `whereNotNull('text->'.$locale)`) with PHP-side aggregation. PostgreSQL, SQLite, and SQL Server safe.
- **Locale pollution + filename injection** (`F4`) — `Import::preview()`/`confirm()` abort 422 when `targetLocale` is not an installed Language code; `TransferExportController::download()` validates same; export filename sanitized via `preg_replace` + `HeaderUtils::makeDisposition()`.
- **Vendor guard as model invariant** (`F5`) — `Translation::forgetTranslation()` now throws `VendorTranslationProtectedException` for vendor rows; guard enforced in both `DatabaseRepository` and `FileRepository`.
- **Wildcard import key rejection** (`F6`) — `RowMapper::resolveIdentity()` rejects key segments containing `*` or `\0`; affected rows are skipped by `ImportCommitService`.
- **ReDoS fix** (`F7`) — type-detection regex: greedy `\[.+\]` → `\[[^\]]+\]`; values > 10 000 chars default to `text` type without regex evaluation.
- **Exception info disclosure** (`F8`) — Import catch blocks call `Log::error()` with the full exception and display a generic localized string; `$e->getMessage()` no longer reaches the Livewire UI.
- **Open-redirect fix** — `ManagesLocale::initLocaleState()` captures `request()->getRequestUri()` (relative path, no host) instead of `url()->current()`. Guard in `changeLocale()` validates with a local-path regex (`/(?![/\])` + no scheme prefix); immune to `APP_URL` host mismatch in dev/staging.
- **RemoveLangCommand locale validation** — locale argument validated against ISO regex before any operation.
- **Asset route outside auth group** — `lingua.assets` served outside the authenticated route group so the language selector CSS/JS is accessible on guest pages (login, public pages).

#### Added

- **Storage driver abstraction** — `database` (default) and `file` drivers controlled by `LINGUA_STORAGE_DRIVER` env / `config('lingua.storage.driver')`. `TranslationRepository` contract with `DatabaseRepository` (language_lines JSON text column) and `FileRepository` (lang/ PHP+JSON) implementations.
- **`lingua:storage {driver} [--force] [--write-env] [--no-migrate]`** — interactive driver switch: syncs translations before switching, warns on html/markdown type-loss when switching to `file`, publishes/runs driver-required migrations, prints `.env` instruction or writes it directly with `--write-env`.
- **`lingua:uninstall [--force] [--keep-config] [--keep-published]`** — safe package teardown: exports DB→lang/ (database driver only, no data loss), drops `language_lines`/`languages`/`lingua_settings` tables, removes published config/views/migrations. `lang/` always preserved.
- **Transfer page** (`/lingua/transfer`, `lingua.transfer` route):
  - **Export** — bilingual (source + one locale), multi-locale (source + all), and JSON-native scopes. Formats: CSV + JSON built-in; XLSX + ODS via optional `openspout/openspout`. Formula-injection guard (cells starting with `= + - @ \t \r` prefixed with `'`). Download via `lingua.transfer.export` (GET, `TransferExportController`).
  - **Import** — `ImportDiffService` dry-run preview (create/update/skip/error counts, capped row lists); `ImportCommitService` transactional commit (type-precedence rules, vendor guard). `TransferSchema`, `RowMapper`, `ParsedRow`, `ImportDiff` DTOs.
  - `FormatRegistry` + 8 format implementations: `CsvWriter`/`CsvReader`, `JsonWriter`/`JsonReader`, `XlsxWriter`/`XlsxReader`, `OdsWriter`/`OdsReader`.
  - `SpreadsheetSupport::available()` gates XLSX/ODS; `SpreadsheetUnavailableException` thrown when requested without openspout.
  
- **Shared navigation menu** — `x-lingua::nav` anonymous Blade component on all 5 admin pages (Languages, Translations, Statistics, Transfer, Settings). Active-page highlighting (`variant="filled"` + `aria-current="page"`). `lingua.nav.enabled` config key (default `true`) + toggle in Settings UI (Routing & Navigation card).
- **Bundled translation dataset** — 26 locales × 7 groups (auth, pagination, passwords, validation, http-statuses, errors, notifications); 5902 strings; aligned to Laravel v13.14.0. Read by `BundledTranslationSource`. Replaces laravel-lang as the provisioning source. Merged into the database during `syncToDatabase()` and `installLocale()`. Bundled notification translations projected into `lang/{locale}.json` via `NotificationProjector` (non-destructive merge, `.lingua-managed.json` sidecar for selective removal on uninstall).
- **`LocaleRegistry`** — internal static dataset (129+ locales) replacing `laravel-lang/common` facade. `LocaleInfo` final readonly VO (`code`, `regional`, `type`, `name`, `native`, `direction`).
- **Statistics page** (`/lingua/statistics`, `lingua.statistics`) — per-language coverage with progress bars, group breakdown, missing-key drill-down, vendor toggle.
- **Settings page** (`/lingua/settings`, `lingua.settings`) — persistent UI settings in `lingua_settings` table; `LinguaSetting` model; `SelectorMode` enum (sidebar/modal/dropdown/headless).
- **Headless language selector** (`lingua::headless-language-selector`) — zero-CSS, `data-lingua-*` attributes, named `$item`/`$current` slots.
- **`ManagesLocale` trait** — shared locale management (`changeLocale()`, `languages()` computed, `currentUrl` capture) for all selector components (sidebar, dropdown, modal, headless).
- **`AtomicFileWriter`** — stateless I/O helper: writes via temp-file + atomic `rename()`; verifies all return values; calls `opcache_invalidate($path, true)` after PHP file writes; `ensureDir()`, `put()`, `putJson()`, `putPhp()` methods.
- **`MigrationPublisher`** — driver-aware selective migration publish. File driver skips `create_language_lines_table`. Idempotent (skips already-published basenames). Used by `lingua:install` and `lingua:storage`.
- **`CacheKey` helper** — canonical cache key builder: `{prefix}.{locale}.{group}` and `{prefix}.{locale}.{vendor}::{group}`.
- **`VendorTranslationProtectedException`** — thrown when attempting to delete a vendor-owned translation. Vendor translations can be edited (value/type) but not deleted.
- **`x-lingua::select`** — custom searchable/clearable select with native Popover API (`popover="manual"` + `showPopover()`/`hidePopover()`). Eliminates Flux modal `transform`/`overflow` stacking context issues. Fallback: `position:fixed` for browsers without Popover API support.
- **`HtmlSanitizer`** — DOM-based whitelist sanitizer with per-tag allowed-attribute map and URI scheme validation.
- **Pro extension hooks** — `suppress_pro_nudge` config, `pro_upgrade_url`, `extensions.enabled` kill-switch; `ExtensionRegistry` for third-party Livewire component injection via `allTranslationTabComponents()` / `allTranslationActionComponents()`.
- **Facade additions** — `get(?string $locale)`, `getDefault()`, `getFallback()`, `available()`, `installed()`, `notInstalled()`, `isInstalled(?string $locale)`, `isAvailable(?string $locale)`, `info(mixed $locale)`, `installDefaultLanguage()`, `updateLanguages()`, `setVendorTranslation()`. `optimize()` deprecated (surgical cache invalidation makes it unnecessary).
- **9-locale UI translations** (ar, en, es, fr, hi, it, pt, ru, zh) for nav menu, Transfer page, Settings routing/nav toggles, and security error messages.
- **`lingua:install` improvements** — arrow-key `select()` driver prompt (CI-friendly numbered fallback); file-mode deploy warnings (Forge/Envoyer/CI overwrite, dirty tree); driver-aware migration publish.
- **`lingua:sync-to-local --force`** — override file-mode no-op guard for deliberate DB→file export.
- **`TranslationFactory`** — `->core()` and `->vendor(string $vendor)` states; `HasFactory` added to `Translation` model.
- **`LangFileKeyParityTest`** — guards key parity across all 9 bundled UI locale files.

#### Changed

- **`LinguaManager` extends `FileLoader`** (not Spatie's `TranslationLoaderManager`); registered via `extend()` on the `translation.loader` binding; DB translations take precedence over file translations at runtime.
- **Vendor translation loading driver-aware** — `LinguaManager::load()` namespace branch checks driver; database mode resolves vendor groups from DB (cached via `getVendorTranslationsForGroup()`); file mode falls back to `parent::load()`.
- **Surgical cache invalidation** — `Cache::forget()` per `(locale, group)` pair on `Translation::saved`/`deleted`. Bulk sync flushes only affected keys. Global `Artisan::call('cache:clear')` removed from all sync paths.
- **`lingua:add` / `lingua:remove` / `lingua:update-lang`** — fully DB-native; no `laravel-lang` file download or Artisan injection via concatenated strings.
- **`Translation::syncToDatabase()`** — two-pass: default locale processed first as key reference; bundled translations merged before app lang files; vendor keys imported only for installed locales; per-row cache suppressed during bulk sync (single bust at end).
- **Language statistics** — PHP aggregation via `Translation::translationCounts()` (multi-DB safe, no JSON-SQL functions).
- **Migrations** — split into three files: `create_language_lines_table`, `create_languages_table`, `create_lingua_settings_table`. `language_lines.text` changed to `nullable()` (no SQL DEFAULT expression). `languages.regional` nullable.
- **Admin UI layout** — Languages toolbar: 2-row flex (Row 1: search + new-language; Row 2: 3 sync/update buttons, DB-mode only). Translations toolbar: `grid grid-cols-12` → `flex flex-wrap`. Section gap standardized to `gap-6` across all 5 admin pages. Modal selector inline style → Tailwind `w-32`.
- **`Language::scopeActive()`** renamed to `scopeOrdered()` for semantic accuracy; `scopeActive()` preserved as a delegate.
- **Asset serving** — CSS/JS served directly from package via `lingua.assets` route (no publish to `public/` required or supported). `lingua-assets` publish tag removed.
- **`lingua:install` migration handling** — driver-scoped `MigrationPublisher::publishFor()` instead of blanket publish-all; file mode never publishes `create_language_lines_table`.
- **`config/lingua.php`** — reorganized into domain groups; new keys: `gate`, `storage.driver`, `nav.enabled`, `links.translations`, `ui.sticky_top`, `cache.store`/`cache.prefix`, `suppress_pro_nudge`, `pro_upgrade_url`, `extensions.enabled`.
- **`Language\Table`** — portable LIKE escaping via `ESCAPE '!'` (MySQL / PostgreSQL / SQLite / SQL Server safe).
- **`LinguaMiddleware`** — DB lookup wrapped in `try/catch`; session write conditional on locale change (avoids marking session dirty on every request).
- **Graceful degrade** — `Lingua` facade read methods, `ManagesLocale::languages()`, `LinguaSetting::get()` catch `QueryException` when Lingua tables are absent (post-uninstall or pre-migration); return safe defaults.
- **`LinguaSeeder` conditional** — database driver only; file-mode install uses `Lingua::installDefaultLanguage()`.

#### Fixed

- Locale switch always redirected to home page — `ManagesLocale::initLocaleState()` now captures `request()->getRequestUri()`; `changeLocale()` guard uses local-path regex immune to `APP_URL` host mismatch.
- Bilingual CSV import skipped all rows when target locale code mismatched column header (e.g. `it_IT` vs `it - Italian`) — `RowMapper::findLocaleValue()` single-candidate fallback.
- File-mode stale translation row after edit — `AtomicFileWriter::putPhp()` calls `opcache_invalidate($path, true)` after atomic rename.
- Language stat accessors (`getTotalStringsAttribute`, `getTranslatedStringsAttribute`) crashed in file-mode with `SQLSTATE[42S02]` — routed through `app(TranslationRepository::class)->counts()`.
- `Import` public `$errors` property shadowed Livewire's `ViewErrorBag` → `@error()` called `getBag()` on an array (fatal) — renamed to `$rowErrors`.
- `transfer` block missing from 8 non-English bundled locales — nav menu and Transfer page showed raw translation keys.
- `DatabaseRepository::installLocale()` not seeding bundled translations for new locales — replaced skip-if-absent guard with `Translation::updateOrCreate()`.
- `Translations::mount()` `TypeError` under `strict_types` — `#[Url]`-bound `bool $showOnlyMissing` received string `"1"` from redundant `request('m', false)` re-read.
- `Translation::countByLocale()` — replaced PostgreSQL-only `whereRaw('(text->>?) IS NOT NULL')` with PHP aggregation.
- `Language::setDefault()` — two UPDATE queries now wrapped in `DB::transaction()`.
- `NotificationProjector::writeJson` — atomic I/O via `AtomicFileWriter`; manifest updated only after successful write.
- Translations resurrect after language delete — `RemoveLangCommand` and `Language\Delete` no longer call `syncToDatabase()` post-deletion.
- `Language\Table` search — `Language::exists()` replaces `.get()->isEmpty()` for bootstrap guard.
- `syncToLocal()` — all `file_put_contents`/`mkdir` calls via `AtomicFileWriter`.
- `syncToDatabase()` — targeted cache invalidation per affected `(locale, group)` pair.
- `Modals::closeModal()` — early return when `$modalName` is empty.
- `WireDirective::getAttributes()` undefined method fatal in `autocomplete.blade.php`.
- `TranslationFactory` — previously called non-existent `getGroupKey()` and swapped `group`/`key` variables.
- Various null-safe operator missing in `Lingua`, `LinguaServiceProvider`, `LinguaMiddleware`, `Translation\Delete::mount()`.

#### Removed

- `spatie/laravel-translation-loader` dependency.
- `laravel-lang/common` and all `LaravelLang\*` service providers.
- `lingua-assets` publish tag (assets served directly via `lingua.assets` route).
- Dead code: `$queryString` manual tracking in `Translations.php`, `getGroupKey()` on `Translation` model, `$canDelete`, `$canSetDefault`, `$syncDatabase`, `$totalStrings` unused properties, redundant facade vendor guard.


---

## [Unreleased]


---

## Lingua 2.0.0 - 2026-06-29

### Breaking Changes

- **Removed `spatie/laravel-translation-loader`** — `Translation` extends `Illuminate\Database\Eloquent\Model` directly. Custom loaders must implement `Rivalex\Lingua\Contracts\TranslationLoader` (method signature `loadTranslations(string $locale, string $group, ?string $namespace = null): array` unchanged).
- **Removed `laravel-lang/common`** — locale metadata served by the internal `LocaleRegistry`. `lingua:add` is DB-native (no `lang:add` call). `lingua:update-lang` no longer downloads files from laravel-lang. Translations provisioned via the bundled dataset.
- **`Lingua::info()` return type** — now returns `?Rivalex\Lingua\Locales\LocaleInfo` (was `LaravelLang\LocaleData`). Property access changed: `->locale->name`→`->name`, `->localized`→`->name`, `->direction->value`→`->direction`, others unchanged. Returns `null` for unknown locales (previously threw).
- **`addLanguage()` / `removeLanguage()` DB-native only** — no filesystem writes. Use `lingua:add` / `lingua:remove` Artisan commands for fully orchestrated operations (files + DB + sync).
- **Route middleware default → `['web', 'auth']`** — admin routes now require authentication by default. Hosts that relied on unauthenticated access must set `'middleware' => ['web']` in `config/lingua.php`. Existing published configs are unaffected.
- **`navigate` default → `false`** — locale switches and page transitions use full-page redirects. Opt back in with `'navigate' => true`.
- **PHP requirement → 8.3+** (was 8.1+). Laravel 11, 12, 13 supported.

### Security

- **HtmlSanitizer** — DOM-based whitelist sanitizer replaces `strip_tags()` in HTML translation preview (`translation/row.blade.php`). `strip_tags()` preserved event handlers and `javascript:` URIs on allowed elements. `HtmlSanitizer::sanitize()` allows only a curated per-tag attribute whitelist; URI attributes validated against `http`/`https`/`mailto` schemes.
- **PathGuard** (`F3`) — LFI/RCE prevention on all filesystem write sinks. `BundledTranslationSource::translationsFor()` and `DatabaseRepository::installLocale()` call `PathGuard::assertSafeSegment()` as first statement.
- **Auth gate** (`F9`) — `'gate' => env('LINGUA_GATE', null)` adds `can:{gate}` middleware to all admin routes for role-based access control. Default `null` — no breaking change for existing installs.
- **Cache DoS** (`F1`) — `Translation::getTranslationsForGroup()` / `getVendorTranslationsForGroup()` skip `rememberForever` for locales that fail the canonical format regex; closes unbounded cache growth.
- **Multi-DB JSON-SQL ban** (`F2`) — `DatabaseRepository::paginate()` (onlyMissing), `byGroup()`, `vendor()`, and `RemoveLangCommand` replaced JSON arrow operators (`text->>$locale`, `whereNotNull('text->'.$locale)`) with PHP-side aggregation. PostgreSQL, SQLite, and SQL Server safe.
- **Locale pollution + filename injection** (`F4`) — `Import::preview()`/`confirm()` abort 422 when `targetLocale` is not an installed Language code; `TransferExportController::download()` validates same; export filename sanitized via `preg_replace` + `HeaderUtils::makeDisposition()`.
- **Vendor guard as model invariant** (`F5`) — `Translation::forgetTranslation()` now throws `VendorTranslationProtectedException` for vendor rows; guard enforced in both `DatabaseRepository` and `FileRepository`.
- **Wildcard import key rejection** (`F6`) — `RowMapper::resolveIdentity()` rejects key segments containing `*` or `\0`; affected rows are skipped by `ImportCommitService`.
- **ReDoS fix** (`F7`) — type-detection regex: greedy `\[.+\]` → `\[[^\]]+\]`; values > 10 000 chars default to `text` type without regex evaluation.
- **Exception info disclosure** (`F8`) — Import catch blocks call `Log::error()` with the full exception and display a generic localized string; `$e->getMessage()` no longer reaches the Livewire UI.
- **Open-redirect fix** — `ManagesLocale::initLocaleState()` captures `request()->getRequestUri()` (relative path, no host) instead of `url()->current()`. Guard in `changeLocale()` validates with a local-path regex (`/(?![/\])` + no scheme prefix); immune to `APP_URL` host mismatch in dev/staging.
- **RemoveLangCommand locale validation** — locale argument validated against ISO regex before any operation.
- **Asset route outside auth group** — `lingua.assets` served outside the authenticated route group so the language selector CSS/JS is accessible on guest pages (login, public pages).

### Added

- **Storage driver abstraction** — `database` (default) and `file` drivers controlled by `LINGUA_STORAGE_DRIVER` env / `config('lingua.storage.driver')`. `TranslationRepository` contract with `DatabaseRepository` (language_lines JSON text column) and `FileRepository` (lang/ PHP+JSON) implementations.
- **`lingua:storage {driver} [--force] [--write-env] [--no-migrate]`** — interactive driver switch: syncs translations before switching, warns on html/markdown type-loss when switching to `file`, publishes/runs driver-required migrations, prints `.env` instruction or writes it directly with `--write-env`.
- **`lingua:uninstall [--force] [--keep-config] [--keep-published]`** — safe package teardown: exports DB→lang/ (database driver only, no data loss), drops `language_lines`/`languages`/`lingua_settings` tables, removes published config/views/migrations. `lang/` always preserved.
- **Transfer page** (`/lingua/transfer`, `lingua.transfer` route):
  - **Export** — bilingual (source + one locale), multi-locale (source + all), and JSON-native scopes. Formats: CSV + JSON built-in; XLSX + ODS via optional `openspout/openspout`. Formula-injection guard (cells starting with `= + - @ \t \r` prefixed with `'`). Download via `lingua.transfer.export` (GET, `TransferExportController`).
  - **Import** — `ImportDiffService` dry-run preview (create/update/skip/error counts, capped row lists); `ImportCommitService` transactional commit (type-precedence rules, vendor guard). `TransferSchema`, `RowMapper`, `ParsedRow`, `ImportDiff` DTOs.
  - `FormatRegistry` + 8 format implementations: `CsvWriter`/`CsvReader`, `JsonWriter`/`JsonReader`, `XlsxWriter`/`XlsxReader`, `OdsWriter`/`OdsReader`.
  - `SpreadsheetSupport::available()` gates XLSX/ODS; `SpreadsheetUnavailableException` thrown when requested without openspout.
  
- **Shared navigation menu** — `x-lingua::nav` anonymous Blade component on all 5 admin pages (Languages, Translations, Statistics, Transfer, Settings). Active-page highlighting (`variant="filled"` + `aria-current="page"`). `lingua.nav.enabled` config key (default `true`) + toggle in Settings UI (Routing & Navigation card).
- **Bundled translation dataset** — 26 locales × 7 groups (auth, pagination, passwords, validation, http-statuses, errors, notifications); 5902 strings; aligned to Laravel v13.14.0. Read by `BundledTranslationSource`. Replaces laravel-lang as the provisioning source. Merged into the database during `syncToDatabase()` and `installLocale()`. Bundled notification translations projected into `lang/{locale}.json` via `NotificationProjector` (non-destructive merge, `.lingua-managed.json` sidecar for selective removal on uninstall).
- **`LocaleRegistry`** — internal static dataset (129+ locales) replacing `laravel-lang/common` facade. `LocaleInfo` final readonly VO (`code`, `regional`, `type`, `name`, `native`, `direction`).
- **Statistics page** (`/lingua/statistics`, `lingua.statistics`) — per-language coverage with progress bars, group breakdown, missing-key drill-down, vendor toggle.
- **Settings page** (`/lingua/settings`, `lingua.settings`) — persistent UI settings in `lingua_settings` table; `LinguaSetting` model; `SelectorMode` enum (sidebar/modal/dropdown/headless).
- **Headless language selector** (`lingua::headless-language-selector`) — zero-CSS, `data-lingua-*` attributes, named `$item`/`$current` slots.
- **`ManagesLocale` trait** — shared locale management (`changeLocale()`, `languages()` computed, `currentUrl` capture) for all selector components (sidebar, dropdown, modal, headless).
- **`AtomicFileWriter`** — stateless I/O helper: writes via temp-file + atomic `rename()`; verifies all return values; calls `opcache_invalidate($path, true)` after PHP file writes; `ensureDir()`, `put()`, `putJson()`, `putPhp()` methods.
- **`MigrationPublisher`** — driver-aware selective migration publish. File driver skips `create_language_lines_table`. Idempotent (skips already-published basenames). Used by `lingua:install` and `lingua:storage`.
- **`CacheKey` helper** — canonical cache key builder: `{prefix}.{locale}.{group}` and `{prefix}.{locale}.{vendor}::{group}`.
- **`VendorTranslationProtectedException`** — thrown when attempting to delete a vendor-owned translation. Vendor translations can be edited (value/type) but not deleted.
- **`x-lingua::select`** — custom searchable/clearable select with native Popover API (`popover="manual"` + `showPopover()`/`hidePopover()`). Eliminates Flux modal `transform`/`overflow` stacking context issues. Fallback: `position:fixed` for browsers without Popover API support.
- **`HtmlSanitizer`** — DOM-based whitelist sanitizer with per-tag allowed-attribute map and URI scheme validation.
- **Pro extension hooks** — `suppress_pro_nudge` config, `pro_upgrade_url`, `extensions.enabled` kill-switch; `ExtensionRegistry` for third-party Livewire component injection via `allTranslationTabComponents()` / `allTranslationActionComponents()`.
- **Facade additions** — `get(?string $locale)`, `getDefault()`, `getFallback()`, `available()`, `installed()`, `notInstalled()`, `isInstalled(?string $locale)`, `isAvailable(?string $locale)`, `info(mixed $locale)`, `installDefaultLanguage()`, `updateLanguages()`, `setVendorTranslation()`. `optimize()` deprecated (surgical cache invalidation makes it unnecessary).
- **9-locale UI translations** (ar, en, es, fr, hi, it, pt, ru, zh) for nav menu, Transfer page, Settings routing/nav toggles, and security error messages.
- **`lingua:install` improvements** — arrow-key `select()` driver prompt (CI-friendly numbered fallback); file-mode deploy warnings (Forge/Envoyer/CI overwrite, dirty tree); driver-aware migration publish.
- **`lingua:sync-to-local --force`** — override file-mode no-op guard for deliberate DB→file export.
- **`TranslationFactory`** — `->core()` and `->vendor(string $vendor)` states; `HasFactory` added to `Translation` model.
- **`LangFileKeyParityTest`** — guards key parity across all 9 bundled UI locale files.

### Changed

- **`LinguaManager` extends `FileLoader`** (not Spatie's `TranslationLoaderManager`); registered via `extend()` on the `translation.loader` binding; DB translations take precedence over file translations at runtime.
- **Vendor translation loading driver-aware** — `LinguaManager::load()` namespace branch checks driver; database mode resolves vendor groups from DB (cached via `getVendorTranslationsForGroup()`); file mode falls back to `parent::load()`.
- **Surgical cache invalidation** — `Cache::forget()` per `(locale, group)` pair on `Translation::saved`/`deleted`. Bulk sync flushes only affected keys. Global `Artisan::call('cache:clear')` removed from all sync paths.
- **`lingua:add` / `lingua:remove` / `lingua:update-lang`** — fully DB-native; no `laravel-lang` file download or Artisan injection via concatenated strings.
- **`Translation::syncToDatabase()`** — two-pass: default locale processed first as key reference; bundled translations merged before app lang files; vendor keys imported only for installed locales; per-row cache suppressed during bulk sync (single bust at end).
- **Language statistics** — PHP aggregation via `Translation::translationCounts()` (multi-DB safe, no JSON-SQL functions).
- **Migrations** — split into three files: `create_language_lines_table`, `create_languages_table`, `create_lingua_settings_table`. `language_lines.text` changed to `nullable()` (no SQL DEFAULT expression). `languages.regional` nullable.
- **Admin UI layout** — Languages toolbar: 2-row flex (Row 1: search + new-language; Row 2: 3 sync/update buttons, DB-mode only). Translations toolbar: `grid grid-cols-12` → `flex flex-wrap`. Section gap standardized to `gap-6` across all 5 admin pages. Modal selector inline style → Tailwind `w-32`.
- **`Language::scopeActive()`** renamed to `scopeOrdered()` for semantic accuracy; `scopeActive()` preserved as a delegate.
- **Asset serving** — CSS/JS served directly from package via `lingua.assets` route (no publish to `public/` required or supported). `lingua-assets` publish tag removed.
- **`lingua:install` migration handling** — driver-scoped `MigrationPublisher::publishFor()` instead of blanket publish-all; file mode never publishes `create_language_lines_table`.
- **`config/lingua.php`** — reorganized into domain groups; new keys: `gate`, `storage.driver`, `nav.enabled`, `links.translations`, `ui.sticky_top`, `cache.store`/`cache.prefix`, `suppress_pro_nudge`, `pro_upgrade_url`, `extensions.enabled`.
- **`Language\Table`** — portable LIKE escaping via `ESCAPE '!'` (MySQL / PostgreSQL / SQLite / SQL Server safe).
- **`LinguaMiddleware`** — DB lookup wrapped in `try/catch`; session write conditional on locale change (avoids marking session dirty on every request).
- **Graceful degrade** — `Lingua` facade read methods, `ManagesLocale::languages()`, `LinguaSetting::get()` catch `QueryException` when Lingua tables are absent (post-uninstall or pre-migration); return safe defaults.
- **`LinguaSeeder` conditional** — database driver only; file-mode install uses `Lingua::installDefaultLanguage()`.

### Fixed

- Locale switch always redirected to home page — `ManagesLocale::initLocaleState()` now captures `request()->getRequestUri()`; `changeLocale()` guard uses local-path regex immune to `APP_URL` host mismatch.
- Bilingual CSV import skipped all rows when target locale code mismatched column header (e.g. `it_IT` vs `it - Italian`) — `RowMapper::findLocaleValue()` single-candidate fallback.
- File-mode stale translation row after edit — `AtomicFileWriter::putPhp()` calls `opcache_invalidate($path, true)` after atomic rename.
- Language stat accessors (`getTotalStringsAttribute`, `getTranslatedStringsAttribute`) crashed in file-mode with `SQLSTATE[42S02]` — routed through `app(TranslationRepository::class)->counts()`.
- `Import` public `$errors` property shadowed Livewire's `ViewErrorBag` → `@error()` called `getBag()` on an array (fatal) — renamed to `$rowErrors`.
- `transfer` block missing from 8 non-English bundled locales — nav menu and Transfer page showed raw translation keys.
- `DatabaseRepository::installLocale()` not seeding bundled translations for new locales — replaced skip-if-absent guard with `Translation::updateOrCreate()`.
- `Translations::mount()` `TypeError` under `strict_types` — `#[Url]`-bound `bool $showOnlyMissing` received string `"1"` from redundant `request('m', false)` re-read.
- `Translation::countByLocale()` — replaced PostgreSQL-only `whereRaw('(text->>?) IS NOT NULL')` with PHP aggregation.
- `Language::setDefault()` — two UPDATE queries now wrapped in `DB::transaction()`.
- `NotificationProjector::writeJson` — atomic I/O via `AtomicFileWriter`; manifest updated only after successful write.
- Translations resurrect after language delete — `RemoveLangCommand` and `Language\Delete` no longer call `syncToDatabase()` post-deletion.
- `Language\Table` search — `Language::exists()` replaces `.get()->isEmpty()` for bootstrap guard.
- `syncToLocal()` — all `file_put_contents`/`mkdir` calls via `AtomicFileWriter`.
- `syncToDatabase()` — targeted cache invalidation per affected `(locale, group)` pair.
- `Modals::closeModal()` — early return when `$modalName` is empty.
- `WireDirective::getAttributes()` undefined method fatal in `autocomplete.blade.php`.
- `TranslationFactory` — previously called non-existent `getGroupKey()` and swapped `group`/`key` variables.
- Various null-safe operator missing in `Lingua`, `LinguaServiceProvider`, `LinguaMiddleware`, `Translation\Delete::mount()`.

### Removed

- `spatie/laravel-translation-loader` dependency.
- `laravel-lang/common` and all `LaravelLang\*` service providers.
- `lingua-assets` publish tag (assets served directly via `lingua.assets` route).
- Dead code: `$queryString` manual tracking in `Translations.php`, `getGroupKey()` on `Translation` model, `$canDelete`, `$canSetDefault`, `$syncDatabase`, `$totalStrings` unused properties, redundant facade vendor guard.


---

## Lingua 1.1.7 - 2026-05-11

### Security

- **[HIGH] Artisan argument injection in Commands** — `AddLangCommand`, `RemoveLangCommand`, `UpdateLangCommand` concatenated locale strings directly into `Artisan::call()` bypassing the `validateLocale()` guard used in `Lingua.php`. Converted all calls to array form `['locales' => [$locale]]` with correct argument name.
- **[HIGH] Artisan injection in `Translation::syncToDatabase()`** — Same concatenation pattern used when bootstrapping the default locale. Fixed to array form.
- **[HIGH] Artisan injection in `LinguaSeeder`** — Same pattern in seeder bootstrap. Fixed to array form.
- **[MEDIUM] Open redirect via Livewire `$currentUrl`** — `ManagesLocale::changeLocale()` redirected to `$currentUrl`, a public Livewire property modifiable via network snapshot. Added same-origin validation: host must match `config('app.url')`, otherwise falls back to `/`.
- **[MEDIUM] Stored XSS in HTML translation preview** — `translation/row.blade.php` rendered `$defaultValue` via `{!! !!}` for all translation types. Raw output now scoped to `html` type only; `text` type uses escaped `{{ }}`.
- **[MEDIUM] Unescaped translation strings in delete modal** — `translation/delete.blade.php` rendered `$deleteHeader` and `$deleteAction` via `{!! !!}`. Changed to `{{ }}`.
- **[MEDIUM] Unvalidated drag-drop parameters in `Language\Sort`** — `updateLanguageOrder()` accepted untyped `$item` and `$position` from Livewire network payload. Added `int` type hints and early return for negative positions.
- **[MEDIUM] Unbounded `$perPage` in Translations** — URL-bound `?p=` parameter had no upper limit, enabling DoS via large result sets. Clamped to `max(1, min(x, 100))` in `mount()`.

### Fixed

- `helpers.php` `linguaLanguageCode()` returned `Stringable` instead of `string` under strict types. Added `->toString()` cast.
- Removed dead `$queryString` manual tracking in `Translations.php`; `updatedCurrentLocale()` now builds redirect params inline from `#[Url]`-tracked properties.
- `LinguaServiceProvider` loader/translator singletons now survive composer script context (null config fallback, DB try/catch).

### Changed

- Added `declare(strict_types=1)` to all remaining PHP source files.
- Removed dead code: `$value` in `Translation\Create`, `getGroupKey()` in `Translation` model, `$syncDatabase`/`$totalStrings` in `Language\Table`, `$canDelete` in `Translation\Delete`, `$canSetDefault` in `Language\SetDefault`, unused `View|Factory` imports in `Language\Delete`, dead `$attribute` in `Translation\Row::validationAttributes()`.


---

## Lingua 1.1.6 - 2026-05-08

### Security

- **[CRITICAL] Path traversal in asset route** — `routes/web.php` `lingua/assets/{path}` used `where('path', '.*')` allowing `../` traversal outside `src/dist/`. Fixed with `realpath()` jail: resolved path must start with the `src/dist` base or request is rejected with 404.
- **[CRITICAL] Default middleware now includes `auth`** — Lingua management routes were publicly accessible by default. Default changed to `['web', 'auth']`. Existing published configs unaffected; new installations now require authentication.
- **[CRITICAL] SQL injection via locale in JSON column path** — `Translations.php` interpolated user-controlled `$currentLocale` directly into raw JSON column paths. Added regex validation; invalid formats fall back to default locale.
- **[HIGH] Path traversal in `syncToLocal()`** — User-controlled `group` values used directly in `file_put_contents()` paths without sanitization. Added `assertSafePathSegment()` guard on locale, group, and vendor before all filesystem writes.
- **[HIGH] Artisan argument injection** — `addLanguage()`, `removeLanguage()`, `updateLanguages()` concatenated locale strings into `Artisan::call()` strings, allowing flag injection. Added `validateLocale()` regex guard before all Artisan calls.
- **[HIGH] TypeError on null Language model** — `Translations.php` declared `public Language $language` (non-nullable) but `::first()` can return null. Changed to `public ?Language $language = null` with null-safe fallback in `mount()`.
- **[MEDIUM] Unvalidated session locale in middleware** — `LinguaMiddleware` applied session locale to `app()->setLocale()` without format validation. Added ISO locale regex check; malformed values fall back to default.

### Fixed

- **[MEDIUM] Wrong unique column in Translation Update** — `rules()` validated `key` against the `group_key` composite column, making uniqueness inoperative. Fixed to scope by `group` + `is_vendor`.
- **[MEDIUM] Double `requiredIf` logic bug** — Two independent `requiredIf` conditions caused cross-field required errors (e.g. `textValue` required when locale=default even if type=html). Fixed to single combined condition.
- **[MEDIUM] Broken markdown type detection** — `writeTranslation()` used `Str::markdown() === $original` which never matched. Replaced with heuristic regex for common markdown markers.
- **[MEDIUM] Missing enum validation on `translationType`** — Both Create and Update accepted any string; invalid types silently produced empty translations. Changed to `Rule::enum(LinguaType::class)`.

### Changed

- Added `declare(strict_types=1)` to all source files missing it.
- Fixed bare `use DB;` in `Models/Language.php` → `use Illuminate\Support\Facades\DB`.


---

## Lingua 1.1.5 - 2026-04-29

### Changed

- **Modal backdrop blur** — Added `backdrop-filter: blur(4px)` to all Flux modal backdrops via `[data-flux-modal] > dialog::backdrop` in `resources/css/lingua/styles.css`. PostCSS prefix-selector scopes the rule to `.lingua [data-flux-modal] > dialog::backdrop`. Both unprefixed and `-webkit-` variants emitted by Lightning CSS for full browser coverage (Chrome, Firefox, Safari).


---

## Lingua 1.1.4 - 2026-04-22

### Fixed

- **Modal centering** — Tailwind preflight scoped under `.lingua *` set `margin: 0` on all descendants, overriding the browser UA `margin: auto` that centers native `<dialog>:modal`. Added explicit `[data-flux-modal] > dialog { margin: auto }` rule in `resources/css/lingua/styles.css` (post-prefixed by postcss-prefix-selector to `.lingua [data-flux-modal] > dialog`). Affects all modal Livewire components: `Language/Create`, `Language/Delete`, `Language/SetDefault`, `Translation/Create`, `Translation/Delete`, `Translation/Update`, and `LanguageSelector` in modal mode.


---

## Lingua 1.1.3 - 2026-04-16

### Changed

- Normalized indentation across all Blade views and PHP source files for consistency.
- Removed unused static assets from the package.


---

## Lingua 1.1.2 - 2026-04-14

### Fixed

- **`Lingua::languages()` serialization** — Method now correctly returns a plain Eloquent `Collection`, fixing a serialization error when the result was stored in a Livewire component property.

### Changed

- **CSS isolation** — All Tailwind utilities, preflight resets, and CSS custom properties are now scoped under `.lingua` via `postcss-prefix-selector`. This prevents Lingua's bundled Tailwind styles from leaking into the host application's global stylesheet. The `lingua.min.css` dist file is rebuilt accordingly.


---

## Lingua 1.1.0 - 2026-04-10

### Added

- **Statistics page** (`/lingua/statistics`, route `lingua.statistics`) — per-language coverage with progress bars,
  breakdown by translation group, missing-key counts with direct links to the translation editor.
  Vendor translations can be included or excluded via a toggle.
  
- **Settings page** (`/lingua/settings`, route `lingua.settings`) — persistent UI settings stored in the
  `lingua_settings` table. Selector mode and flag display are now configurable from the UI without editing config files
  or redeploying.
  
- **`LinguaSetting` model** — key/value store for package settings with typed get/set API and automatic fallback to
  `config/lingua.php`. Known keys: `selector.show_flags` (bool) and `selector.mode` (string).
  
- **`SelectorMode` enum** — backed string enum with four cases: `sidebar`, `modal`, `dropdown`, `headless`.
  Each case provides a `label()` and `description()` method; `selectValues()` returns all cases as value/label pairs.
  
- **Headless language selector** (`lingua::headless-language-selector`) — zero-CSS Livewire component rendering
  semantic HTML with `data-lingua-*` attributes (`data-lingua-selector`, `data-lingua-list`, `data-lingua-item`,
  `data-lingua-active`, `data-lingua-button`, `data-lingua-name`, `data-lingua-native`, `data-lingua-code`) and named
  slots (`$item`, `$current`) for full styling freedom.
  
- **`ManagesLocale` trait** — extracts shared locale management logic (`languages()`, `changeLocale()`,
  `currentLocale`) used by both `LanguageSelector` and `HeadlessLanguageSelector`.
  
- **Translation files** for `ar`, `es`, `fr`, `hi`, `it`, `pt`, `ru`, `zh` — complete translations of all Lingua UI
  strings including the new statistics and settings sections.
  

### Changed

- **`Translation::syncToDatabase()`** — refactored to a two-pass approach: default locale processed first and used as
  the reference key set; non-default locale keys are skipped if absent from the default locale; vendor keys are
  imported only when the locale is installed in the `languages` table. A `$syncing` flag suppresses per-row
  `cache:clear` calls during bulk sync, firing once at the end instead. No existing DB records are ever deleted.
  
- **Migration structure** — `create_lingua_table` split into three separate files: `create_language_lines_table`,
  `create_languages_table`, `create_lingua_settings_table`. Granular rollback is now possible per table.
  
- **`Language::scopeActive()`** — renamed to `scopeOrdered()` for semantic accuracy; `scopeActive()` preserved as a
  delegate with `@todo` for future `is_active` field filtering.
  
- **Asset serving** — compiled assets are now served directly from the package via the `lingua.assets` route.
  Publishing assets is no longer required or supported; the `lingua-assets` publish tag has been removed.
  
- **Language selector config** — `selector.mode` now accepts `headless` as a valid value in addition to the existing
  `sidebar`, `modal`, and `dropdown` options.
  

### Fixed

- **`languages` migration** — removed erroneous standalone `->unique()` on the `regional` column; the composite
  unique index `unique_language_type` on `[code, regional]` is the correct constraint.
  
- **Statistics `includeVendor` toggle** — replaced conflicting `wire:model.live` + `wire:change` directives with
  `:checked` binding + `wire:change`, eliminating the double-toggle that caused the switch to have no effect.
  


---

## Upgrading to 1.1.0

Run migrations to create the new `lingua_settings` table:

    php artisan migrate
    
If you previously published assets, they are no longer needed. The package now serves its own compiled assets
automatically. You can safely delete `public/vendor/lingua/` from your project.

If you have customised `config/lingua.php`, your values continue to work as fallback — no changes required.


---

## Lingua 1.0.3 - 2026-04-09

### 2026-04-09

#### Fixed

- **`Translation/Create` — group preserved after creation** — the `group` field is now retained after a successful save, allowing multiple keys to be added to the same group consecutively without reselecting it. Only `key`, `translationType`, and value fields are reset.
- **`Translation/Create` and `Translation/Update` — whitespace normalization** — `group` and `key` values are sanitized with `Str::squish()->trim()` before being persisted, preventing keys with leading, trailing, or excess internal spaces from being stored.

#### Tests

- `CreateTest`: corrected `group` assertions to reflect preservation after creation; added whitespace normalization test for `group` and `key`.
- `UpdateTest`: added whitespace normalization test for `group` and `key`; added test confirming that vendor translation `group` and `key` fields are immutable.

## Rivalex Lingua - 2026-04-09

### 2026-04-09

#### Fixed

- **`Translation/Create` — group preserved after creation** — the `group` field is now retained after a successful save, allowing multiple keys to be added to the same group consecutively without reselecting it. Only `key`, `translationType`, and value fields are reset.
- **`Translation/Create` and `Translation/Update` — whitespace normalization** — `group` and `key` values are sanitized with `Str::squish()->trim()` before being persisted, preventing keys with leading, trailing, or excess internal spaces from being stored.

#### Tests

- `CreateTest`: corrected `group` assertions to reflect preservation after creation; added whitespace normalization test for `group` and `key`.
- `UpdateTest`: added whitespace normalization test for `group` and `key`; added test confirming that vendor translation `group` and `key` fields are immutable.


---

## Rivalex Lingua - 2026-04-01

### 2026-04-01

#### Fixed

- **`Lingua::updateLanguages()` / `lingua:update-lang`** — `lang:update` was called without arguments, causing laravel-lang to refresh translation files for every locale present in the vendor filesystem, including locales not installed in the `languages` table. Both the facade method and the Artisan command now resolve the installed locales from the database and pass them explicitly to `lang:update {locales}`. If no languages are installed the update is skipped entirely.


---

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
  
