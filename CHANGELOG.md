# Changelog

All notable changes to `lingua` will be documented in this file.

## [Unreleased]

### UI — Transfer page redesign (feat/remove-spatie-translation-loader)

#### Changed
- **`style(ui): Transfer Export/Import layout`** — collapsed 5-card Export sprawl and 3-card Import sprawl into single `x-lingua::card` containers using `x-lingua::card.row` rows (label/desc left col, control right col, `divide-y`). Matches the Settings page pattern. Footer action buttons pinned to card bottom with `border-t`. Alert messages extracted to shared `resources/views/transfer/partials/_alerts.blade.php` (safe `isset()` guards for components without `$successMessage`).

---

### Fix — Import `$errors` property collision (feat/remove-spatie-translation-loader)

#### Fixed
- **`fix(livewire): Import $errors shadows ViewErrorBag`** — `public array $errors` in `Import.php` was injected into the view scope by Livewire, clobbering the `ViewErrorBag` instance. `@error('file')` called `getBag()` on an array → fatal on `/lingua/transfer`. Renamed to `$rowErrors` (3 occurrences; property never rendered in Blade).

#### Tests
- **`feat(tests): Livewire::test smoke tests for Transfer/Export/Import`** — replaced `render()→instanceof View` assertions (which do not compile Blade) with `Livewire::test(...)->assertOk()`. Catches property-collision, missing component registration, and `@error`/`@foreach` runtime crashes that the previous pattern missed.

---

### Phase 6b — Translation Import / Export (feat/remove-spatie-translation-loader)

#### Added
- **`feat(transfer): translation export`** — `ExportService` produces bilingual, multi-locale, and JSON-native exports via `FormatRegistry` writers (CSV, JSON built-in; XLSX/ODS via optional `openspout/openspout`).
- **`feat(transfer): translation import dry-run`** — `ImportDiffService` parses CSV/JSON/XLSX/ODS files and returns an `ImportDiff` with create/update/skip/error counts and capped row lists. No writes performed.
- **`feat(transfer): translation import commit`** — `ImportCommitService` re-parses the file and applies changes in a DB transaction (database mode) or sequential writes (file mode). Enforces type-precedence rules (plan §8) and vendor guard (never creates/deletes vendor rows).
- **`feat(ui): Transfer page`** — `lingua.transfer` route hosts `Transfer`, `Export`, `Import` Livewire components. Export redirects to `lingua.transfer.export` (HTTP download route via `TransferExportController`). Import uses `WithFileUploads` for preview→confirm flow.
- **`feat(schema): transfer column layout`** — `TransferSchema` is single source of truth for header names; `RowMapper` handles `TranslationLine↔row` conversion and identity reconstruction (existence-match first, then first-dot split for new keys).
- **`feat(formats): CSV formula injection guard`** — cells starting with `= + - @ \t \r` are prefixed with `'` in CSV and XLSX/ODS writers.
- **`feat(formats): OpenSpout optional`** — `SpreadsheetSupport::available()` gates XLSX/ODS. `FormatRegistry::availableFormats()` filters them when absent; `SpreadsheetUnavailableException` thrown on unavailable format request.
- **`feat(i18n): transfer lang keys`** — `transfer.*` keys added to `resources/lang/en/lingua.php`.
- **`feat(nav): transfer link`** — nav button to `lingua.transfer` added to languages, translations, and settings views.
- **`suggest: openspout/openspout`** added to `composer.json`.

#### Files Created
- `src/Transfer/Enums/TransferScope.php`, `TransferFilter.php`
- `src/Transfer/TransferSchema.php`, `RowMapper.php`, `ParsedRow.php`, `ImportDiff.php`
- `src/Transfer/SpreadsheetSupport.php`, `ExportService.php`, `ImportDiffService.php`, `ImportCommitService.php`
- `src/Transfer/Format/FormatWriter.php`, `FormatReader.php`, `FormatRegistry.php`
- `src/Transfer/Format/CsvWriter.php`, `CsvReader.php`, `JsonWriter.php`, `JsonReader.php`
- `src/Transfer/Format/XlsxWriter.php`, `XlsxReader.php`, `OdsWriter.php`, `OdsReader.php`
- `src/Transfer/Format/SpreadsheetUnavailableException.php`
- `src/Livewire/Transfer.php`, `Export.php`, `Import.php`
- `src/Http/Controllers/TransferExportController.php`
- `resources/views/transfer.blade.php`, `export.blade.php`, `import.blade.php`
- `tests/Feature/Transfer/RowMapperTest.php`, `CsvRoundTripTest.php`, `JsonRoundTripTest.php`
- `tests/Feature/Transfer/ExportServiceTest.php`, `SpreadsheetRoundTripTest.php`
- `tests/Feature/Transfer/ImportDiffServiceTest.php`, `ImportCommitServiceTest.php`, `TransferUiTest.php`

#### Tests
- 79 new tests (737 total, baseline was 658).

---

### Phase 6a — Driver-Aware Vendor Load Path (feat/remove-spatie-translation-loader)

#### Bug Fixes
- **`fix(loader): vendor namespaces now served from DB in database mode`** — `LinguaManager::load()` previously unconditionally returned `parent::load()` (file) for any namespaced group, creating a hybrid source of truth. Vendor edits in the DB had no runtime effect. Fixed: namespace branch is now driver-aware — database mode resolves via `Translation::getVendorTranslationsForGroup()` (cached `rememberForever`), merged over file translations; file mode returns `parent::load()` unchanged.
- **`fix(cache): vendor cache key collision`** — `CacheKey::forGroup()` was vendor-blind. New `CacheKey::forVendorGroup($locale, $vendor, $group)` uses `{prefix}.{locale}.{vendor}::{group}`. Cache bust paths in `forgetCacheForLocales()` and `syncToDatabase()` are now vendor-aware.
- **`fix(guard): VendorTranslationProtectedException relocated to repository layer`** — guard was facade-only. Now lives in `DatabaseRepository::deleteKey/forgetLocale` and `FileRepository::deleteKey/forgetLocale` (both drivers). Facade throw removed. `setValue`/`create` on vendor rows remain allowed.

#### Changed
- `src/TranslationManager/CacheKey.php` — added `forVendorGroup()`.
- `src/Models/Translation.php` — added `getVendorTranslationsForGroup()`; bust paths vendor-aware.
- `src/Database/Db.php` — namespace lookups call `getVendorTranslationsForGroup()`.
- `src/TranslationManager/LinguaManager.php` — vendor branch driver-aware via `instanceof DatabaseRepository`.
- `src/Database/DatabaseRepository.php` — `deleteKey`/`forgetLocale` throw for vendor rows.
- `src/Storage/FileRepository.php` — `deleteKey`/`forgetLocale` throw for vendor rows (previously silent no-op).
- `src/Lingua.php` — removed redundant vendor guard from `forgetTranslation()`.

#### Tests
- 13 new tests (658 total, baseline was 645).

---

### Phase 11 — Realign bundled translations to Laravel 13 (feat/remove-spatie-translation-loader)

#### Bug Fixes
- **Missing `doesnt_contain` and `encoding` validation strings in all 25 locales** — the translated locales were generated at Laravel v12.19.3 while the EN reference was regenerated at v13.14.0. Two new validation rules (`doesnt_contain`, `encoding`) introduced in L13 were never propagated to non-EN locales. Fixed by re-running the Haiku generator at `--laravel-tag=v13.14.0` for `validation` group; manifest idempotency skips the 134 already-translated keys, translating only the 2 new ones per locale (50 total API strings, 0 discarded).
- **Missing `Reset your password` and `Verify your email address` notification subjects in all 25 locales** — notification files carried the stale L12 key `Reset Password Notification` (removed in L13) and lacked the renamed subjects and the now-separate `Verify Email Address` action label. Root cause: `NotificationSource::SEMANTIC_PATTERNS` matched `'Notification'` substring (gone in L13) for `reset_subject` and exact-matched the old `'Verify Email Address'` for `verify_subject`. Fixed patterns: `reset_subject` → exact `'Reset your password'`; `verify_subject` → exact `'Verify your email address'`; added `verify_action` → exact `'Verify Email Address'` to capture the action button label (closes 9/9 key parity between EN and all locales).
- All 25 locales now have 0 missing / 0 stale validation keys and 0 missing / 0 stale notification strings. `resources/translations/.meta.json` updated to `laravel_tag: v13.14.0`, full group list, `total_strings: 5902`.

#### Changed
- `build-tools/src/Source/NotificationSource.php` — `SEMANTIC_PATTERNS` updated for L13 subject renames; `verify_action` entry added.
- `resources/translations/{locale}/validation.php` × 25 locales — `doesnt_contain` and `encoding` keys added.
- `resources/notifications/{locale}.php` × 25 locales — realigned to 9 strings, stale `Reset Password Notification` key dropped.
- `resources/translations/.meta.json` — bumped to `v13.14.0`, full group list, `total_strings: 5902`.

### Phase 10 — Select popover via native Popover API (feat/remove-spatie-translation-loader)

#### Bug Fixes
- **Custom select popover stretches/scrolls Flux modals** — Phase 9's teleport-into-dialog approach failed because the Flux `<dialog>` has `transform: matrix(1,0,0,1,0,0)` (identity but non-`none`), making it the containing block for both `absolute` and `fixed` descendants; combined with `overflow:auto`, any positioned child that overflows the dialog's height inflated its `scrollHeight` and added an internal scrollbar (the "block che allunga la modal"). Root fix: promote the popover to the browser top layer via the native **Popover API** (`popover="manual"` attribute + `showPopover()`/`hidePopover()`). A shown popover's containing block is the viewport, bypassing all ancestor `overflow` and `transform` constraints. Removed: dialog teleport, `modal` prop, `_teleportTarget` plumbing. The popover node stays inside `[data-lingua-select]`; only its painting moves to the top layer. Both modal and non-modal selects now use a single `position:fixed` viewport-coordinate path in `positionPopover()`.
- **`x-show` inline `display:none` fighting `showPopover()`** — Alpine's `x-show="open"` sets `style="display:none"` when `open=false`. `showPopover()` removes the UA `[popover]:not(:popover-open){display:none!important}` rule but cannot override an inline `display:none`. Fixed by calling `pop.style.removeProperty('display')` immediately before `showPopover()`. Graceful fallback: browsers without Popover API support retain plain `position:fixed` behaviour.

#### Changed
- `src/Views/Components/select.blade.php` — `popover="manual"` + `m-0` added to popover div; `modal` prop and `$teleportTarget` derivation removed.
- `resources/js/lingua.js` — `linguaSelect`: `teleport`/`_teleportTarget` state removed; `init()` teleport block removed; `openSelect()` calls `removeProperty('display')` + `showPopover()`; `closeSelect()` calls `hidePopover()`; `destroy()` calls `hidePopover()` on teardown; `positionPopover()` collapsed to single fixed-viewport branch with UA `inset/margin` reset.
- `resources/views/{language/create,translation/create,translation/update}.blade.php` — `:modal="$modalName"` binding removed (no longer needed).

### Phase 9 — Select-in-modal popover anchor + DB locale seeding (feat/remove-spatie-translation-loader)

#### Bug Fixes
- **Custom select popover mispositioned in Flux modals** — Three prior attempts failed: (a) `position:absolute` inside the form was clipped by modal `overflow:hidden`; (b) body `x-teleport` + `fixed z-9999` rendered behind the Flux `<dialog>` top layer and overlay; (c) `position:fixed` inside the dialog was retargeted by Flux's open `transform` animation, flowing the popover after the footer and breaking modal layout. Root fix: add a `modal` prop to `x-lingua::select` that moves the popover DOM node into the Flux `<dialog>` element (`data-modal="<name>"`). The native `<dialog>` lives in the browser top layer (above the overlay), is `position:absolute` per UA stylesheet (safe offset parent), and has no transform. JS `positionPopover()` uses `position:absolute` offsets relative to the dialog rect in modal mode, and retains `position:fixed` viewport math for non-modal selects. Prop wired to all three affected modals: Add Language, Create Translation, Update Translation.
- **`DatabaseRepository::installLocale` not seeding bundled translations** — Adding a new language via the UI created the `Language` record but wrote nothing into `language_lines.text[$locale]`. The prior `installLocale()` implementation skipped any row not already present (`if ($existing === null) continue`). Fixed by replacing the guard with `Translation::updateOrCreate(...)`, creating rows when absent — same create-if-absent guarantee the `FileRepository` provides. Bundled values only; keys absent from the bundle remain "missing". Affects database driver only; file driver unchanged.

#### Tests
- Rewired `tests/Feature/Livewire/Language/CreateTest.php` "can add new language" to point `lingua.base_translations_path` at the real shipped bundle and assert `validation.required[it]` is non-empty — previously a false green due to empty test fixture path.
- Added `DatabaseRepositoryTest::installLocale seeds bundled values for a new locale` and `…is a no-op for the default locale`.
- Fixed pre-existing flaky test isolation: `TestCase::defineEnvironment()` now wipes non-default locale files from `tests/tmp/lang` (e.g. stale `it.json`) and copies bundled `en/*.php` translations deterministically, replacing accumulated-file dependency. `TestCase::setUp()` resets `app()->setFallbackLocale()` to neutralise `LinguaMiddleware` side-effects. `CacheInvalidationTest` updated to use the `auth` group (always present in the bundled `en` fixture) instead of `single`.

### Phase 7 — Graceful degrade when Lingua tables absent (feat/remove-spatie-translation-loader)

#### Bug Fixes
- **Runtime crash post-uninstall / pre-install** — After `lingua:uninstall` drops tables (but before `composer remove`), or when the package is loaded before migrations are run, any request that rendered a language selector or called `Lingua::hasLocale()` / `getName()` / `getDirection()` / `isDefaultLocale()` / `getDefaultLocale()` threw `QueryException: Table 'languages' does not exist`. Fixed by wrapping all six runtime read methods in `Lingua` with a centralized `safeRead()` helper (try/catch `QueryException`) that returns documented safe defaults (`false`, `''`, `'ltr'`). Mirrors the existing pattern in `LinguaMiddleware` and `registerTranslator()`.
- **`ManagesLocale::languages()` crash post-uninstall** — Livewire selector components using the `ManagesLocale` trait now catch `QueryException` in the `languages()` computed property and return an empty collection, so the selector renders with no items instead of throwing.
- **`LinguaSetting::get()` crash post-uninstall** — `LanguageSelector::mount()` calls `LinguaSetting::get()` which queries `lingua_settings`. Now wrapped in try/catch `QueryException`; returns the provided `$default` when the table is absent.

### Phase 6 — Install/driver/uninstall overhaul (feat/remove-spatie-translation-loader)

#### Added
- **`MigrationPublisher`** (`src/Support/MigrationPublisher.php`) — driver-aware selective migration publisher. Copies only the migrations required by the chosen driver (`language_lines` skipped in file mode). Idempotent: skips basenames already present. Used by `lingua:install` and `lingua:storage`.
- **`lingua:uninstall`** (`src/Commands/UninstallCommand.php`) — safe package teardown: exports DB translations to `lang/` files first (database driver only, no data loss), drops three Lingua tables, removes published config and views/migrations. `lang/` files always preserved. Options: `--force`, `--keep-config`, `--keep-published`.
- **Arrow-key driver selector** — `lingua:install` now uses `Laravel\Prompts\select()` instead of a numbered `choice()` prompt. Falls back to standard choice in non-interactive/CI environments.

#### Changed
- **`lingua:install` migration handling** — Replaced blanket `->publishMigrations()->askToRunMigrations()` (all three files always) with driver-scoped `MigrationPublisher::publishFor($driver)` + confirm-to-migrate in `endWith`. File mode no longer publishes or runs `create_language_lines_table`.
- **`lingua:storage {driver}`** — Now calls `MigrationPublisher::ensureMigrations()` before syncing: if the target driver's required migrations are not yet published, publishes and (unless `--no-migrate`) runs them. Prevents `syncToDatabase()` crashing on a missing `language_lines` table after a driver switch.
- **`lingua:storage` signature** — Added `{--no-migrate}` option: publish missing migrations but do not run them.

### Phase 5b — File-mode bootstrap fix (feat/remove-spatie-translation-loader)

#### Bug Fixes
- **File-mode `lang/` never created on add-language** — `Lingua::addLanguage()` now calls `TranslationRepository::installLocale()`. In file mode writes `lang/{locale}.json` + `lang/{locale}/*.php` (bundled + default-locale key mirror). In DB mode identical to before.
- **File-mode default language never bootstrapped** — New `Lingua::installDefaultLanguage()` creates default `Language` record and seeds storage. Called by `lingua:install` (file driver) and lazily by `Languages` mount when no languages exist in file mode.
- **Sync UI shown in file mode** — `languages.blade.php` gates sync buttons behind `@unless($fileMode)`. `Languages` component exposes `$fileMode`; server-side no-op guards added to all three sync actions.

#### Refactor
- **`TranslationRepository` contract** — new `installLocale(string $locale): void`; `DatabaseRepository` → `syncToDatabase()`; `FileRepository` → writes lang files.
- **`Language\Create` + `AddLangCommand`** — removed redundant `Translation::syncToDatabase()` calls.

### Phase 5 — Residual hardening (feat/remove-spatie-translation-loader)

#### Security
- **`HtmlSanitizer`** (`src/Support/HtmlSanitizer.php`) — New DOM-based whitelist sanitizer replaces `strip_tags()` in `Translation\Row`. `strip_tags()` removed disallowed tags but preserved ALL attributes on allowed ones (event handlers, `javascript:` URIs) — a stored XSS vector in the admin HTML preview (`{!! !!}`). `HtmlSanitizer::sanitize()` parses with `DOMDocument`, unwraps non-whitelisted elements (preserving text content), drops any attribute not explicitly allowed per-tag, and validates URI attributes against an `http`/`https`/`mailto` scheme whitelist.
- **`RemoveLangCommand` locale-format validation** — Validates the locale argument against `/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*$/` before it reaches any JSON path expression.

#### Added
- **Bundled dataset wired into `syncToDatabase()`** (`src/Models/Translation.php`) — Pass 1 (default locale): bundled base translations merged first, app lang files appended (app overrides bundled for same key). Pass 2 (remaining locales): bundled content only for INSTALLED locales — never auto-installs the whole bundled catalogue.
- **`TranslationFactory` rewrite** — Previous factory called non-existent `Translation::getGroupKey()` and swapped `group`/`key` variables. Factory now only sets composable fields; the model's `creating`/`saving` hooks compute `group_key`. Added `->core()` and `->vendor(string $vendor)` factory states. `HasFactory` trait added to `Translation`.

#### Fixed
- **Translations resurrect after language delete** — `RemoveLangCommand` and `Language\Delete` no longer call `syncToDatabase()` after deletion. Re-syncing post-removal would re-import the locale from `lang/{locale}` files, silently undoing the deletion. `Language\Delete` also fixed a double-delete: `Lingua::removeLanguage()` already deletes the Language record; the redundant `$this->language->delete()` call is removed.
- **Migrations multi-DB** — `language_lines.text` changed from `NOT NULL DEFAULT (JSON_ARRAY())` (MySQL/modern-PG/MSSQL-2022 syntax, broke PG < 16 at migration time) to `nullable()` with no SQL default. `languages.regional` changed to `nullable()` — unknown locales and several registry entries legitimately have no regional variant.
- **`DatabaseRepository::paginate` onlyMissing parity** — Now counts empty-string values (`''`) as missing, matching `FileRepository` and `Statistics::isTranslated()` definition.
- **`Translations::mount` TypeError** — Removed redundant `request('q'/'p'/'g'/'m')` re-reading in `mount()`. The `#[Url]` attributes already bind these properties; the previous `request('m', false)` assigned a string to a typed `bool` property — a fatal `TypeError` under `strict_types` with `?m=1`.
- **`LinguaMiddleware` pre-migration safety** — DB lookup wrapped in `try/catch(\Throwable)` so a missing table (pre-migration) or unavailable DB never takes down the whole request. Session write now conditional on change (avoids marking session dirty on every request).
- **`Language\Table` portable LIKE escaping** — Wildcard characters escaped with `!` and declared via `ESCAPE '!'`. Backslash escaping without an explicit `ESCAPE` clause is MySQL/PG-only; SQLite and SQL Server treated it literally, breaking search silently. `exists()` replaces `active()->get()->isEmpty()` for the bootstrap guard.
- **`Modals::closeModal` early return** — Avoids calling `Flux::modal('')->close()` when `$modalName` is empty.
- **`routes/web.php` asset route** — Moved outside the auth-protected route group. The language selector can be embedded on guest pages; its CSS/JS must be reachable without authentication.

#### Breaking changes (host app notice)
- **Route middleware default** — `config('lingua.middleware')` now defaults to `['web', 'auth']` (was `'web'`). Host apps relying on the old default to serve lingua routes without authentication must set `'middleware' => ['web']` explicitly in `config/lingua.php`.

#### Tests
- `tests/Unit/HtmlSanitizerTest.php` — 13 cases covering whitelist, XSS vectors (event handlers, `javascript:`/`data:` URIs, obfuscated schemes, iframes), Unicode, blank input.
- `tests/Feature/Sync/BundledSyncTest.php` — 5 cases: bundled default-locale import, bundled non-default installed locale, bundled content NOT imported for uninstalled locales, app-override-bundled precedence, no-resurrect regression.
- `tests/Feature/Commands/RemoveLangCommandTest.php`, `tests/Feature/Livewire/Language/DeleteTest.php` — Updated to mock-free no-resurrect regression tests.

### Added

- **§8 test coverage (Phase 4 closure)** — 3 new test files covering §8 cases 4, 9, 11: `PathAlignmentTest` (driver=file resolves FileRepository at configured `lang_dir`, write→read round-trip); `FacadeFileModeTest` (`Lingua::getTranslation/getTranslations/getTranslationByGroup/setTranslation` in file-mode, `languages()` invariant on DB); `ComponentsFileModeTest` (Statistics + Translations render correctly from file data). 584/584 tests green, pint clean.
- **`InstallCommand` driver selection** (`src/LinguaServiceProvider.php`) — `lingua:install` prompts `choice('Translation storage driver?', ['database', 'file'], 0)`. Prints `LINGUA_STORAGE_DRIVER={driver}` `.env` instruction (no auto-write). File driver: 4 `warn()` lines about deploy pipeline risks (Forge/Envoyer/CI overwrite, dirty working tree). `endWith` seeder (`LinguaSeeder`) conditional on `driver === 'database'` — file-mode install skips seeding (lang files are the source of truth).
- **`SetStorageDriverCommand`** (`src/Commands/SetStorageDriverCommand.php`) — New `lingua:storage {driver : database|file} {--force} {--write-env}` command. Counts html/markdown rows in PHP (no SQL JSON), warns + confirms before DB→file switch; syncs translations before switching; prints `LINGUA_STORAGE_DRIVER={driver}` `.env` instruction (or writes `.env` with `--write-env`).
- **`SyncToLocalCommand --force`** — File-mode guard: without `--force` the command is a no-op with a warning; with `--force` it asks for explicit confirmation before proceeding.
- **`SyncToDatabaseCommand` file-mode note** — Prints `Note: file-mode active — DB is a staging copy only.` when driver is `file` (non-blocking).

- **`AtomicFileWriter`** (`src/Support/AtomicFileWriter.php`) — Internal `final` stateless I/O helper. Writes via temp-file + atomic `rename`; `json_encode` with `JSON_THROW_ON_ERROR`; verifies every `file_put_contents`/`rename` return; removes temp on any failure. Methods: `put`, `putJson`, `putPhp`, `ensureDir`.

### Fixed

- **`NotificationProjector::writeJson` atomic I/O** — Replaced bare `mkdir`/`file_put_contents` (return values ignored, `json_encode` could produce `false`) with `AtomicFileWriter::putJson`. JSON encode errors no longer silently write `"false\n"` over user files.
- **`NotificationProjector` manifest ordering** — `project()` and `unproject()` now guarantee the file operation succeeds (or throws) _before_ `updateManagedManifest` runs, preventing manifest divergence on write failure.
- **`BundledTranslationSource` dead `.json` branch removed** — Sibling `{locale}.json` path was unreachable (`available()` uses `GLOB_ONLYDIR`) and lacked `json_decode` guards. Removed; only per-group PHP files are loaded, matching Phase 2 dataset design.
- **`Translation::countByLocale` multi-DB fix** — Replaced `whereRaw('(text->>?) IS NOT NULL', [$locale])` (PostgreSQL-only) with PHP aggregation via `translationCounts()`. Works on SQLite, MySQL, PostgreSQL, SQL Server; no SQL JSON functions.
- **`Language` statistics — PHP aggregation, no SQL JSON** — Removed `jsonKeyExistsExpression` / `match($driver)` 4-dialect JSON-SQL. `scopeWithStatistics` is now a passthrough (call-site compatible). Four computed properties (`total_strings`, `translated_strings`, `missing_strings`, `completion_percentage`) implemented as Eloquent accessors backed by `Translation::translationCounts()`.
- **`Translation::syncToLocal` robust I/O** — All `file_put_contents`/`mkdir` calls replaced with `AtomicFileWriter`; errors throw instead of silently producing partial files.
- **`Translation::syncToDatabase` targeted cache invalidation** — Replaced `Artisan::call('cache:clear')` (wiped entire application cache) with per-`(locale, group)` `Cache::store()->forget(CacheKey::forGroup(...))` on keys actually touched during sync. Unrelated cache entries are preserved.

### Phase 3 — Test isolation fixes + pint config

- **`pint.json`** — Added to exclude `build-tools/cache/` (downloaded Laravel framework files) from style checks.
- **`DeleteTest`** — Changed locale from `it` to `af` (Afrikaans): Italian is now pre-seeded via bundled dataset, causing `Language::where('code','it')->exists()` to return `true` before the test adds it.
- **`TableTest` COMPUTED** — Changed `it`/`es` to `af`/`am`: both are pre-seeded by `syncToDatabase()` at seeder time; non-bundled locales pass `assertDatabaseMissing`.
- **`TableTest` SEARCH** — Added delete of pre-seeded `it`/`ar` records before `Language::create()` to prevent UNIQUE constraint violations.
- **`LanguageSelectorTest`** — Changed `assertCount('languages', 1)` to `assertCount('languages', Language::count())`: seeder now creates 26 Language records (all bundled locales).

### Phase 3 — Bug fixes (missing use imports in Language, Translation, LinguaServiceProvider)

- **`Language.php` missing `use Illuminate\Support\Facades\DB`** — `setDefault()` called `DB::transaction()` without the facade import, causing `Class "Rivalex\Lingua\Models\DB" not found` at runtime. Import added.
- **`LinguaServiceProvider.php` missing `use Rivalex\Lingua\Support\AtomicFileWriter`** — `AtomicFileWriter::class` resolved to `Rivalex\Lingua\AtomicFileWriter` (wrong namespace) in `register()`. Import added.
- **`Translation.php` missing `use Rivalex\Lingua\Support\AtomicFileWriter`** — Same resolution bug in `syncToLocal()`: resolved to `Rivalex\Lingua\Models\AtomicFileWriter`. Import added.

### Phase 3 — EN bundled dataset

- **`resources/translations/en/`** — English locale added to bundled dataset as a direct copy of Laravel framework `v13.14.0` EN strings (no translation, no Haiku). 5 groups: `auth`, `pagination`, `passwords`, `validation`, `http-statuses`. Read by `BundledTranslationSource` like any other locale; users can freely edit the strings.
- **`resources/notifications/en.php`** — English notification identity map (source EN = value). 9 strings from `ResetPassword` + `VerifyEmail` at `v13.14.0`.
- **`resources/translations/.dataset-lock.json`** — Version lock file for the EN source tag (`v13.14.0`, resolved dynamically from GitHub API and locked for reproducibility). `--refresh-tag` re-resolves the latest stable 13.x release; subsequent runs use the locked tag.
- **`build-tools/src/Source/TagResolver`** — Fetches latest stable `v13.x.y` release from GitHub releases API. Reads/writes `.dataset-lock.json`. Skips pre-releases and drafts; sorts candidates by semver descending.
- **`build-tools/src/Command/GenerateEnCommand`** — New `generate:en` CLI command: resolves tag via `TagResolver`, writes EN files as direct source copies (bypasses Haiku and `ValidationGate`), writes identity notification map, updates lock + `.meta.json`. Options: `--refresh-tag`, `--test-fixtures`, `--laravel-tag`, `--force`.
- **`build-tools/src/Source/NotificationSource::loadAllStrings()`** — New public method: returns all `Lang::get()` strings extracted from notification classes without semantic-key mapping. Used by `GenerateEnCommand` to bypass `SEMANTIC_PATTERNS` (subject strings changed in v13.x).
- **`tests/tmp/lang/en/`** — Test fixtures regenerated from same `v13.14.0` EN source: `auth.php`, `pagination.php`, `passwords.php`, `validation.php`, `http-statuses.php`. Consistent with bundled dataset.

### Phase 2 — Bundled translation dataset

- **Phase 2 — Bundled translation dataset** (`resources/translations/`). 25 locales × 5 groups (`auth`, `pagination`, `passwords`, `validation`, `http-statuses`) machine-translated from Laravel framework `v12.19.3` EN strings via Claude Haiku. Read directly by `BundledTranslationSource` with zero runtime changes. 0% discard rate.
- **Bundled notification translations** (`resources/notifications/`). 25 locales × 8 email strings (password reset + email verify) projected into user app's `lang/{locale}.json` at locale install-time via `NotificationProjector`, enabling `Lang::getFromJson()` resolution out-of-the-box.
- **`NotificationProjector`** (`src/Locales/NotificationProjector.php`) — Merge is non-destructive (user keys never overwritten), idempotent, with selective removal on locale uninstall via `.lingua-managed.json` sidecar manifest.
- **`lingua.base_notifications_path` config key** — Configurable path for bundled notification translations.
- **`build-tools/` offline build tool** (`rivalex/lingua-build-tools`). Standalone PHP CLI (Symfony Console + Guzzle) that downloads pinned Laravel EN lang files, translates them in batches via Anthropic Messages API, validates token/pipe/choice preservation with a gate, and writes idempotent output. Excluded from Composer distribution via `export-ignore`.
- **`build-tools/src/Source/SymfonyStatusSource`** — RFC HTTP status codes (Symfony table + Laravel/nginx/Cloudflare extras) as `[code => text]`.
- **`build-tools/src/Source/NotificationSource`** — Downloads `VerifyEmail.php` + `ResetPassword.php` at pinned tag, extracts exact EN strings passed to `Lang::get()`, maps to 8 semantic keys.
- **`build-tools/src/Output/NotificationWriter`** — Writes `resources/notifications/{locale}.php` as `['source EN' => 'translation']`.
- **`build-tools/src/Translation/ValidationGate`** — Post-translation gate: pipe count, placeholder frequency, no-new-placeholder, choice token checks. Discards non-conforming strings with explicit reason.
- **`build-tools/cache/manifest.json`** — Source-hash–keyed idempotency manifest; enables incremental re-runs and resume after interruption.
- **`resources/translations/.meta.json`** — Run metadata: Laravel tag, model, timestamp, locale/group list, total/discard counts.
- **`tests/Unit/BundledTranslationSourceTest.php`** — Verifies `available()` returns all 25 generated locales and `translationsFor()` returns valid flat entries.
- **`tests/Unit/Locales/NotificationProjectorTest.php`** — 12 tests: project/unproject lifecycle, non-destructive merge, selective removal, sidecar manifest, no-op guards, path safety.

---

## Lingua 2.0.0 - 2026-06-08

### Breaking Changes

- **Removed `laravel-lang/common` dependency.** All locale metadata now served by the internal `LocaleRegistry`. See `UPGRADE.md` for migration.
- **`Lingua::info()` return type changed** from `LocaleData` to `?LocaleInfo`. Property access updated: `->locale->name` / `->localized` → `->name`; `->direction->value` → `->direction` (string).
- **`addLanguage()` / `removeLanguage()` no longer write to filesystem.** DB-native only; `lang:add` / `lang:rm` are no longer invoked. Translation files must be pre-populated or will be provided by Phase 2 bundled dataset.
- **Removed `spatie/laravel-translation-loader` dependency.** `Translation` model now extends `Illuminate\Database\Eloquent\Model` directly. Custom translation loaders must implement `Rivalex\Lingua\Contracts\TranslationLoader`.
- **`LinguaManager` now extends `Illuminate\Translation\FileLoader`** instead of Spatie's `TranslationLoaderManager`.

### Added

- **`LocaleRegistry` service** — Static locale dataset (129 locales) replaces `laravel-lang/common` facade. Singleton binding, resolves by `code` and `regional`. API: `all()`, `info()`, `availableCodes()`, `has()`.
- **`LocaleInfo` value object** — `final readonly` VO with `code`, `regional`, `type`, `name`, `native`, `direction` (all strings).
- **`BaseTranslationSource` contract** — Extension point for Phase 2 bundled translation dataset. Methods: `available(): array<string>`, `translationsFor(string): array`.
- **`BundledTranslationSource`** — Phase 1 no-op implementation; reads from `resources/translations/` (empty until Phase 2).
- **`lingua.base_translations_path` config key** — Configurable path for bundled translation dataset.
- **Flexible lang routing** — Optional route parameters (`routes_extra_parameters`), direct embed mode (no route), configurable `navigate` flag, layout override via `layout` config key.
- **`links.translations` config block** — `enabled` flag + `route` key to toggle and customize the translations management link in the language switcher row.
- **`ui.sticky_top` setting** — Configurable top offset (px/rem) for the sticky filter bar; persisted in `lingua_settings` via `LinguaSetting`.
- **Settings page partials** — `_routing.blade.php`, `_editor.blade.php` (13 toolbar toggles), `_save.blade.php`. `_selector.blade.php` migrated to `flux:select`.
- **Autocomplete component** — Flux Pro `flux:listbox` + Alpine.js fallback; dead `autocomplete.css` removed.
- **Config reordered** — `config/lingua.php` reorganised into 5 domain groups: `routing`, `ui`, `cache`, `features`, `links`.
- `Rivalex\Lingua\Contracts\TranslationLoader` — internal contract replacing Spatie's interface.
- `Rivalex\Lingua\Exceptions\InvalidConfiguration` — typed exception replacing Spatie's version.
- `Rivalex\Lingua\TranslationManager\CacheKey` — helper that builds `lingua.trans.{locale}.{group}` cache keys.
- `Translation::getTranslationsForGroup(string $locale, string $group): array` — DB query with `Cache::rememberForever` per (locale, group) pair.
- `config('lingua.cache.store')` and `config('lingua.cache.prefix')` — optional cache driver and key prefix override.
- `static::deleted` hook on `Translation` — forgets cache keys for all locales in `text` when a record is deleted.

### Changed

- `LinguaSeeder`, `Translation::syncToDatabase()`, `LanguageFactory` migrated from `Locales::` facade to `LocaleRegistry`.
- `Lingua::updateLanguages()` — DB-native sync; no longer invokes `lang:update` or `lang:rm`.
- **Cache invalidation is now surgical.** On `Translation::saved`, only the affected `(locale, group)` cache keys are forgotten. Global clear still issued once at end of `syncToDatabase()`.
- `LinguaServiceProvider::registerLoader()` uses `extend()` to correctly wrap any underlying `translation.loader` binding; moved from `boot()` to `register()` phase.
- All 8 locale files updated with new settings translation keys (`routing`, `editor` sections).
- `Settings.php` Livewire component: 6 new properties, validation rules, and persistence for routing/editor/UI settings.
- **Tailwind `@source` glob** extended to `src/**/*.php`.

### Fixed

- `WireDirective::getAttributes()` undefined method fatal in `autocomplete.blade.php` — replaced with safe attribute forwarding.
- `sticky_top` setting not persisting — views now read from `LinguaSetting` instead of hardcoded config.

### Removed

- `laravel-lang/common` and all `LaravelLang\*` service providers.
- `spatie/laravel-translation-loader` dependency.

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
  
