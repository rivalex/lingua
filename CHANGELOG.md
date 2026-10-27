# Changelog

All notable changes to `lingua` will be documented in this file.

## 3.0.0 - 2026-10-27

### Security

- **Admin UI denied by default** - the `manage-lingua` ability (`lingua.gate`) is now required. Until your
  application defines it, access is allowed only in the `local` environment and answers 403 elsewhere. Set
  `LINGUA_GATE=` (empty) to opt out and rely on authentication only. Every admin Livewire component now authorizes the
  gate itself, so embedding a component outside the Lingua routes is protected too. The installer warns about it.
- **Gate honoured with older published configs** - a published config that still has
  `'gate' => env('LINGUA_GATE', null)` now enforces `manage-lingua`. Only an empty string or `false` opts out.
- **Lazy-loaded admin components are authorized** - components loaded lazily are checked against the gate on every
  call and update, not only on the first render.
- **Middleware note** - the `lingua.middleware` setting is documented as not being an authorization mechanism on
  Livewire requests: only `auth` and the `can:` gate are checked again, so `role:` middleware alone is not enough.
- **Markup sanitized on write** - `html` values always go through the sanitizer and are cleaned. `text` and `markdown`
  values that contain disallowed HTML (scripts, event-handler attributes, unlisted tags) are rejected with a validation
  error, an import row error or an exception instead of being rewritten; truncated tags and sequences such as `a<b` are
  rejected too. Values without markup are stored byte for byte. Markdown autolinks (`<https://...>`) are not accepted:
  use `[text](url)`. Files synchronized from your own `lang/` directory are trusted and untouched.
- **Safer URIs in translations** - protocol-relative (`//host`) and backslash URIs are removed by the sanitizer. A link
  built from a placeholder needs a fixed prefix: `href=":url"` is removed from an `html` value and makes a `text` or
  `markdown` value rejected, while `href="/:path"` and `href="https://example.com/:path"` keep working.
- **Attribute-printed keys rejected** - a value for `Pagination Navigation`, `pagination.previous` or `pagination.next`
  that contains `"`, `<` or `>` is rejected, because Laravel's pagination views print these keys inside an HTML
  attribute. Other values are stored byte for byte.
- **Group and key validation** - group and vendor names must match `^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$` (or be `single`);
  keys must be non-empty, at most 300 characters, free of control characters and free of a `*` dot segment. Outside
  `single`, keys are also free of `*` and empty dot segments. Enforced on the UI, import and facade writes.
- **Key and group collision rules** - a key may not contain `|`; a new key may not be a dot-prefix of an existing key in
  the same group, nor have an existing key as its dot-prefix; a new group may not equal an existing group ignoring case.
  `single` keys are literal in file mode.
- **Markup decided independently of libxml** - `text` and `markdown` values are checked by Lingua's own HTML5 tag
  scanner, so the result no longer depends on the libxml build of your PHP. HTML comments, unbalanced or mismatched
  tags, repeated attributes, unknown tags and attributes, and URIs that hide characters behind numeric references
  without `;` are rejected.
- **Attribute-printed keys, complete** - the JSON forms of `pagination.previous` and `pagination.next` are covered by
  the same rule, and `html` values are checked after sanitizing.
- **Re-validation on type and key changes** - changing the type, group or key of a translation checks the value of
  every language again, and the change is rejected as a whole if one value is not acceptable.
- **Export and extensions authorized** - the export endpoint authorizes the gate on its own, and extension components
  that do not use the `AuthorizesLingua` trait are no longer rendered (the registry reports them).
- **Fallback gate without guests** - the fallback gate used when no ability is defined now requires an authenticated
  user.
- **Escaped values in admin views** - values interpolated in the admin views are escaped.
- **Locked Livewire properties** - properties that no view binds can no longer be changed from the browser. The
  `Translations` page validates the selected locale on the server, `uiStickyTop` accepts only a CSS length, and a
  locale switch redirects only to a same-site relative path.
- **The default language cannot be deleted** - the UI and `Lingua::removeLanguage()` refuse. Deleting a language leaves
  vendor translations alone and runs in a transaction in database mode.
- **Assets route** - accepts only paths made of letters, digits, `/`, `_`, `.` and `-`.
- **Atomic file writes** - the temporary file is created exclusively, the target file mode is kept and the temporary
  file is removed on every failure.
- **Delete confirmation** - the confirmation phrase is uppercased with a multibyte-safe function.
- **Rich-text editor** - `html` values pass through DOMPurify on every load and save path in the editor.
- **Dependencies** - Livewire, Tiptap, DOMPurify and the Vite toolchain are updated to releases without known
  vulnerabilities at release time (versions under Changed).

### Added

- **Installer adds the Tailwind source line** - `lingua:install` offers to add
  `@source '../../vendor/rivalex/lingua/resources/views';` to the host `resources/css/app.css` (after the last
  `@source`, or the last `@import`, or at the top) and reminds you to rebuild your assets. If you decline, with
  `--no-interaction`, or when the file is missing, it changes nothing and prints the line to add.

### Changed

- **Declared dependencies** - `laravel/framework` (`^12.0 || ^13.0`) and `laravel/prompts` (`^0.3`) are required
  explicitly instead of arriving through Livewire and Flux; `illuminate/contracts` is no longer listed.
- **Distribution** - 3.0.0 is the first release published from the rebuilt public repository; earlier versions are no
  longer available on Packagist. Applications on Laravel 11 have no supported release.
- **deps: front-end and Composer dependencies updated** - `livewire/livewire` `^4.4.8` (earlier 4.3.x and 4.4.x
  releases have a published security advisory), Tiptap 3.32, DOMPurify 3.4.17, Vite and its plugins.
- **Build: source maps hidden** - the shipped JavaScript no longer references a source map.
- **ci: the public repository runs only read-only workflows** - Dependabot, automatic merges, code style
  commits and changelog commits now run only in the development repository. The public test workflow declares
  `permissions: contents: read`.
- **ci: Composer dist archives exclude development files** - tests, build configuration and front-end sources
  are no longer shipped in `vendor/rivalex/lingua`.
- **docs: SECURITY.md** - how to report a vulnerability privately.
- **Flux 2.21 or newer is required.**
- **Settings page without unused fields** - the Settings page no longer shows `navigate`, `layout`, the translation
  links and the editor toolbar: they were saved but nothing read them. They stay configurable in `config/lingua.php`.
- **Optional `WritesTranslationBatches` interface** - a translation repository may implement it to write many values
  at once; the import uses it for updates (one write per lang file in file mode, one transaction in database mode).
  `TranslationRepository` is unchanged.
- **dev: `laravel/pao`** - replaces the abandoned `nunomaduro/pao` as a development dependency.
- **Breaking: no shipped CSS** - Lingua 3.0.0 stops shipping CSS. The host compiles the panel classes through
  `@source '../../vendor/rivalex/lingua/resources/views';` in its own stylesheet, so the panel follows the host theme.
- **Anonymous components moved to `resources/views/components`** - the Blade components now live with the other
  package views, so `vendor:publish --tag=lingua-views` also publishes them to
  `resources/views/vendor/lingua/components`.
- **Views use literal Tailwind utilities and host tokens only** - the custom classes of the former stylesheet (editor,
  translation row, modals, branding, language sort) are written as literal utilities in the views. Neutrals are `zinc`,
  emphasis is the Flux `accent` token (replacing `violet`), and inline styles are gone except computed values. The
  logos follow the host color scheme. `LinguaType::iconColor()` is replaced by the `x-lingua::type-icon` component.
- **Documentation for the host theme** - the README gains the Tailwind v4 requirement, a "Styling" section (the
  `@source` line, the installer behaviour, rebuilding assets) and a "Theme" section; the upgrade notes cover the
  `@source` line, the removed dark mode setting and the published components that override the package ones. The
  "default language" badge in the statistics page now uses the accent colour.

### Removed

- **Breaking: `outhebox/blade-flags` and `blade-ui-kit/blade-icons`** - Lingua no longer requires them and ships the
  flags it uses (`resources/flags`, with their licence notice). Applications that call `@svg()`, `svg()` or
  `<x-flag-...>` from these packages must require `outhebox/blade-flags` themselves.
- **`spatie/laravel-package-tools`** - replaced by a plain service provider. Publish tags (`lingua-config`,
  `lingua-migrations`, `lingua-translations`, `lingua-views`) and their destinations are unchanged.
- **`league/html-to-markdown`** - removed; it was never used.
- **Bundled Alpine.js** - `alpinejs`, `@alpinejs/ui` and `@alpinejs/focus` are no longer part of the JavaScript
  bundle (about 100 KB smaller). Lingua registers its components on Livewire's Alpine instance and logs a console error
  when Alpine is missing.
- **Installer prompt** - `lingua:install` no longer asks to star the repository.
- **Force dark mode** - removed: the config `dark_mode`, `LINGUA_FORCE_DARK_MODE`, the Appearance card in Settings,
  `LinguaSetting::KEY_DARK_MODE_FORCE` and `<x-lingua::dark-scope>`. The panel follows the host dark mode. An existing
  `dark_mode.force` row in `lingua_settings` can be deleted.
- **Breaking: the CSS bundle and the Tailwind build** - `src/dist/css/lingua.min.css`, `resources/css` and the
  `tailwindcss`, `@tailwindcss/vite` and `postcss-prefix-selector` dev dependencies are removed. The asset route serves
  JavaScript and images only and answers 404 for `.css` paths. The host compiles the panel classes with its own
  stylesheet.

### Fixed

- **Regional flags on Linux** - flags such as `de_AT` are found on case-sensitive filesystems too; a locale without
  its own flag falls back to its base language, then to a neutral flag.
- **JSON strings in database mode** - single-string translations are served from the `single` rows, and in file mode the
  database loader returns nothing, so a stale table is ignored. Clear the cache store Lingua uses after upgrading (see the upgrade guide): the cached JSON strings changed shape.
- **Fallback locale outside web requests** - the fallback locale setup of the translator also applies in queue workers
  and console commands.
- **JSON translation files** - they are always written as objects, even when every key is numeric.
- **Key length and whitespace rules** - keys are at most 300 characters (the column size) and may not have leading,
  trailing or repeated whitespace; a new key that appears more than once in one import file is a row error.
- **Default language and translation edits** - re-adding the default language keeps it as the default; deleting a
  translation is refused when the default language changed after the dialog opened; outside the default language and
  in file mode, the group, key and type of a translation cannot be changed, and this is now enforced on the server.
- **Notifications for new languages** - `base_notifications_path` now defaults to `null` (the files bundled with the
  package). A config published before 3.0 points into your application: set it to `null`.
- **Wildcard keys served from the database** - validation messages whose keys have a `*` segment (for example
  `custom.items.*.name.required`) are no longer dropped when the group is loaded from the database. Keys with dots
  are written into existing lang files in the shape the file already has.
- **Cache after a group rename and inside a transaction** - saving a row forgets the cache of the group it left and
  of its new group, immediately and again after the surrounding transaction commits, so a value read and cached
  inside the transaction does not stay stale after the commit. A group with no rows is no longer cached.
- **Sync to local keeps file-only keys** - the database values are merged into the lang files; keys that
  exist only in the files, and the shape of the files, are kept.
- **Removed languages stay removed** - a language you removed is not recreated by the next sync to database. Adding it
  again clears that.
- **One default language** - re-seeding or installing the default language flags the configured locale as default only
  when no default exists or it already is the default.
- **`lingua:remove` in file mode** - works in file mode too.
- **Commands exit with failure** - `lingua:add`, `lingua:remove`, `lingua:update-lang` and `lingua:sync-to-local`
  return exit code 1 on every error and refusal.
- **Facade lookups by `group.key`** - `getTranslation()`, `getTranslations()`, `setTranslation()` and
  `forgetTranslation()` resolve `group.key`, `vendor::group.key` and `single.<sentence>`. A bare key is accepted only
  when exactly one translation has it; otherwise nothing is found and nothing is changed.
- **Concurrent edits** - in database mode a value change locks the row inside a transaction; in file mode every lang
  file read-modify-write holds one file lock. Changing a translation to the `html` type stores the sanitized value of
  every language.
- **Session locale** - a locale stored in the session is used only when it is an installed language code, and
  switching to a regional code (for example `it_IT`) stores the language code.
- **Import and export round trips** - values starting with `-`, `+`, `=` or `@` (and values that already start with a
  quote) come back unchanged from CSV, XLSX and ODS exports. A JSON export can be imported again.
- **Sentence keys in an import** - a new key whose text before the first dot contains a space (for example
  `Welcome back. Please log in.`) is created as a `single` key instead of being split at the first dot.
- **Keys that differ only by case** - a new key that differs only by case from an existing key of its group, or
  from one created earlier in the same import file, is rejected, in the `single` group too.
- **Import speed in file mode** - updates are written once per lang file instead of once per value.
- **Languages page queries** - the translation counts are read once per request.
- **Delete messages** - a failed language deletion shows its own message instead of the sorting error, and
  deleting the default language shows why it is refused. The dialog that deletes one language's value of a
  translation shows the language name in bold again, escaped.

## 2.1.1 - 2026-10-21

### Changed

- **Public mirror** - the package is now published from the public repository
  `rivalex/lingua`. Releases v2.0.0 to v2.1.0 are not republished there: v2.1.1 is
  the first v2 release available from the mirror. Versions 1.x are unchanged.
- **ci: test results uploaded with `codecov-action`** - replaces the deprecated
  `codecov/test-results-action` (`report_type: test_results`).

### Fixed

- **docs(readme): badges** - the Laravel badge now reads 12-13 (Laravel 11 was dropped in
  2.1.0) and the Codecov badge points to the `master` branch.
- **docs(changelog): 2.0.6 folded into 2.1.0** - the 2.0.6 tag was never published, its
  changes shipped in 2.1.0. Applications on Laravel 11 stay on 2.0.5.

## 2.1.0 - 2026-10-18

### Removed

- **Laravel 11 support** - `illuminate/contracts` now requires `^12.0 || ^13.0` and the dev dependency `orchestra/testbench` `^10.9 || ^11.0`. Laravel 11 reached the end of security support on March 12, 2026, and its stable releases no longer resolve with current Composer defaults (only `11.x-dev` does). Applications on Laravel 11 keep receiving Lingua 2.0.5. See UPGRADE.md.

### Fixed

- **fix(cache): a translation cache store outage returned HTTP 500** - in production, `RedisException: Connection timed out` thrown by `Redis->pconnect()` inside `rememberForever()` (from `Translation::getTranslationsForGroup()`, reached by a Blade `__()` call) broke the public home page. The database is the source of truth and the cache only an optimisation, so the read path now degrades: `getTranslationsForGroup()` and `getVendorTranslationsForGroup()` read the cache and write back after a miss inside a guarded helper; on failure the exception is reported and the values come straight from the database. A request-scoped circuit breaker (`CacheCircuit`, bound as scoped so it resets between Octane requests and queue jobs) makes the first failure skip the store for the rest of the request, instead of every translation group retrying the connection and waiting for the timeout. Database errors are not caught (the missing-table fallback in `LinguaManager::load()` still works) and an invalid cache store name stays a loud error. Write-path invalidation (`forgetCacheForLocales()` and the forget in `syncToDatabase()`) is intentionally unchanged: a swallowed failed forget would leave a forever-cached stale entry alive after the store recovers.
- **fix(loader): vendor rows leaked into app groups with the same name** - `Translation::getTranslationsForGroup()`, which serves the app namespace in database mode, filtered by `group` only, so a vendor row (`is_vendor = true`) in a group named like an app group was merged into the app translations and could overwrite app keys. It now reads non-vendor rows only; vendor rows keep being served by `getVendorTranslationsForGroup()`. Upgrade note: app groups already cached forever may still hold leaked vendor keys, so clear the translation cache once after upgrading (`php artisan cache:clear`, or forget the `lingua.trans.*` keys on the configured store).

### Changed

- **ci: test matrix extended to PHP 8.3 and Laravel 13** - `run-tests.yml` now runs PHP 8.3 and 8.4 against Laravel 12 and 13 (previously PHP 8.4 with Laravel 12 only). On PHP 8.3 the dev-only `openspout/openspout`, which requires PHP 8.4, is removed before install and the XLSX/ODS tests skip.

## 2.0.5 - 2026-09-25

### Fixed

- **perf(sync): `syncToDatabase()` timed out behind reverse proxies** - every entry did its own lookup plus an `updateOrCreate()` (two SELECTs and one write per key per locale, in autocommit). On a remote database this is latency-bound: a real app with ~1,150 rows issued 6,389 queries per sync, which exceeded Cloudflare's 100 s edge timeout when triggered from the Languages UI (HTTP 504). Existing rows are now loaded once into an in-memory index, unchanged values are skipped, only changed or new rows are written, and both passes run in a single transaction (no partial state if the request is killed). Cache invalidation is deduplicated per locale/group. Same app: 19 queries, 0.15 s (was 4.33 s on a local database). Row-creation semantics (type detection, `group_key`, int-key cast) are unchanged.
- **fix(command): `lingua:sync-to-database` exited 0 on failure** - the exception was printed but swallowed, so deploy hooks and CI could not detect a failed sync. The command now returns `FAILURE`.

## 2.0.4 - 2026-07-28

### Fixed

- **`codecov/patch` check failing on 2.0.3 dark-mode commit** - patch coverage sat at 19.04% against a 67.79% target because `Settings::$forceDarkMode`'s mount/save logic (`src/Livewire/Settings.php`) had no test coverage. Added four tests mirroring the existing `showFlags` pattern: config-default load, DB-override load, persistence on `save()`, and an `assertSee` for the new "Appearance" card. 794/796 suite green (2 pre-existing skips).

## 2.0.3 - 2026-07-28

### Fixed

- **feat: force dark mode option** - Lingua's own Tailwind build compiled `dark:` utilities under the default `@media (prefers-color-scheme: dark)` strategy, scoped `.lingua .dark\:*`, fully decoupled from any host app's class-based dark toggle (`<html class="dark">`). Package styling only rendered when the *browser/OS* preference was dark, regardless of the host app's own theme state - verified via chrome-devtools (emulated light color-scheme, compiled CSS inspection). Switched the Tailwind entry (`resources/css/lingua.css`) to a class-based `dark:` variant (`@custom-variant dark (&:where(.dark, .dark *))`); `vite.config.js`'s `postcss-prefix-selector` transform already anticipated this (`:where(.dark,...) .lingua <rest>` reconstruction) but the directive was never added. Added `config('lingua.dark_mode.force')` (env `LINGUA_FORCE_DARK_MODE`) + `LinguaSetting::KEY_DARK_MODE_FORCE` DB override + Settings UI toggle ("Appearance" card) so Lingua's dark theme can be forced on regardless of host app / OS state - same config+DB+UI precedent as `nav.enabled`. New `x-lingua::dark-scope` anonymous component wraps the `.lingua` root on all 8 package views (5 full pages + 3 selector variants) with a conditional `.dark` ancestor. 9-locale i18n for the new Appearance card. 790/792 tests green (2 pre-existing skips).

## 2.0.2 - 2026-07-18

- Changelog update only (no code changes).

## 2.0.1 - 2026-07-18

### Fixed

- **`group_key` NOT NULL violation on seed (PostgreSQL)** - `Translation::create()`/`updateOrCreate()` could omit `group_key` from the INSERT, crashing `migrate:fresh --seed` (and any `Lingua::addLanguage()` call) with `SQLSTATE[23502]` on PostgreSQL. Root cause: the model populated `group_key` through two overlapping mechanisms (a dirty-gated `creating`/`saving` pair, plus a `groupKey()` Attribute `set:` closure) that could drift out of sync. First consolidated into a single unconditional `saving()` hook - but that alone was still insufficient: under `artisan migrate:fresh --seed` the Eloquent event dispatcher can be unbound when `Translation` first boots, so the `saving()` listener silently never registers and `group_key` stays absent from the INSERT regardless. Fixed for real by making population event-**independent**: `Translation::save()` is overridden to recompute `group_key` immediately before every `parent::save()` call, and `writeTranslation()` / `DatabaseRepository::installLocale()` also set it explicitly in their `updateOrCreate()` payloads as a belt-and-braces measure (`group_key` added to `$fillable`; `buildGroupKey()` made `public static`). Added end-to-end regression coverage (`tests/Feature/Models/TranslationGroupKeyTest.php`), including cases run under `Translation::withoutEvents()` that reproduce the dispatcher-unbound failure deterministically and would have failed against the `saving()`-hook-only fix.

## 2.0.0 - 2026-06-30

### Lingua 2.0.0 - 2026-06-29

#### Breaking Changes

- **Removed `spatie/laravel-translation-loader`** - `Translation` extends `Illuminate\Database\Eloquent\Model` directly. Custom loaders must implement `Rivalex\Lingua\Contracts\TranslationLoader` (method signature `loadTranslations(string $locale, string $group, ?string $namespace = null): array` unchanged).
- **Removed `laravel-lang/common`** - locale metadata served by the internal `LocaleRegistry`. `lingua:add` is DB-native (no `lang:add` call). `lingua:update-lang` no longer downloads files from laravel-lang. Translations provisioned via the bundled dataset.
- **`Lingua::info()` return type** - now returns `?Rivalex\Lingua\Locales\LocaleInfo` (was `LaravelLang\LocaleData`). Property access changed: `->locale->name`→`->name`, `->localized`→`->name`, `->direction->value`→`->direction`, others unchanged. Returns `null` for unknown locales (previously threw).
- **`addLanguage()` / `removeLanguage()` DB-native only** - no filesystem writes. Use `lingua:add` / `lingua:remove` Artisan commands for fully orchestrated operations (files + DB + sync).
- **Route middleware default → `['web', 'auth']`** - admin routes now require authentication by default. Hosts that relied on unauthenticated access must set `'middleware' => ['web']` in `config/lingua.php`. Existing published configs are unaffected.
- **`navigate` default → `false`** - locale switches and page transitions use full-page redirects. Opt back in with `'navigate' => true`.
- **PHP requirement → 8.3+** (was 8.1+). Laravel 11, 12, 13 supported.

#### Security

- **HtmlSanitizer** - DOM-based whitelist sanitizer replaces `strip_tags()` in HTML translation preview (`translation/row.blade.php`). `strip_tags()` preserved event handlers and `javascript:` URIs on allowed elements. `HtmlSanitizer::sanitize()` allows only a curated per-tag attribute whitelist; URI attributes validated against `http`/`https`/`mailto` schemes.
- **PathGuard** (`F3`) - LFI/RCE prevention on all filesystem write sinks. `BundledTranslationSource::translationsFor()` and `DatabaseRepository::installLocale()` call `PathGuard::assertSafeSegment()` as first statement.
- **Auth gate** (`F9`) - `'gate' => env('LINGUA_GATE', null)` adds `can:{gate}` middleware to all admin routes for role-based access control. Default `null` - no breaking change for existing installs.
- **Cache DoS** (`F1`) - `Translation::getTranslationsForGroup()` / `getVendorTranslationsForGroup()` skip `rememberForever` for locales that fail the canonical format regex; closes unbounded cache growth.
- **Multi-DB JSON-SQL ban** (`F2`) - `DatabaseRepository::paginate()` (onlyMissing), `byGroup()`, `vendor()`, and `RemoveLangCommand` replaced JSON arrow operators (`text->>$locale`, `whereNotNull('text->'.$locale)`) with PHP-side aggregation. PostgreSQL, SQLite, and SQL Server safe.
- **Locale pollution + filename injection** (`F4`) - `Import::preview()`/`confirm()` abort 422 when `targetLocale` is not an installed Language code; `TransferExportController::download()` validates same; export filename sanitized via `preg_replace` + `HeaderUtils::makeDisposition()`.
- **Vendor guard as model invariant** (`F5`) - `Translation::forgetTranslation()` now throws `VendorTranslationProtectedException` for vendor rows; guard enforced in both `DatabaseRepository` and `FileRepository`.
- **Wildcard import key rejection** (`F6`) - `RowMapper::resolveIdentity()` rejects key segments containing `*` or `\0`; affected rows are skipped by `ImportCommitService`.
- **ReDoS fix** (`F7`) - type-detection regex: greedy `\[.+\]` → `\[[^\]]+\]`; values > 10 000 chars default to `text` type without regex evaluation.
- **Exception info disclosure** (`F8`) - Import catch blocks call `Log::error()` with the full exception and display a generic localized string; `$e->getMessage()` no longer reaches the Livewire UI.
- **Open-redirect fix** - `ManagesLocale::initLocaleState()` captures `request()->getRequestUri()` (relative path, no host) instead of `url()->current()`. Guard in `changeLocale()` validates with a local-path regex (`/(?![/\])` + no scheme prefix); immune to `APP_URL` host mismatch in dev/staging.
- **RemoveLangCommand locale validation** - locale argument validated against ISO regex before any operation.
- **Asset route outside auth group** - `lingua.assets` served outside the authenticated route group so the language selector CSS/JS is accessible on guest pages (login, public pages).

#### Added

- **Storage driver abstraction** - `database` (default) and `file` drivers controlled by `LINGUA_STORAGE_DRIVER` env / `config('lingua.storage.driver')`. `TranslationRepository` contract with `DatabaseRepository` (language_lines JSON text column) and `FileRepository` (lang/ PHP+JSON) implementations.
  
- **`lingua:storage {driver} [--force] [--write-env] [--no-migrate]`** - interactive driver switch: syncs translations before switching, warns on html/markdown type-loss when switching to `file`, publishes/runs driver-required migrations, prints `.env` instruction or writes it directly with `--write-env`.
  
- **`lingua:uninstall [--force] [--keep-config] [--keep-published]`** - safe package teardown: exports DB→lang/ (database driver only, no data loss), drops `language_lines`/`languages`/`lingua_settings` tables, removes published config/views/migrations. `lang/` always preserved.
  
- **Transfer page** (`/lingua/transfer`, `lingua.transfer` route):
  
  - **Export** - bilingual (source + one locale), multi-locale (source + all), and JSON-native scopes. Formats: CSV + JSON built-in; XLSX + ODS via optional `openspout/openspout`. Formula-injection guard (cells starting with `= + - @ \t \r` prefixed with `'`). Download via `lingua.transfer.export` (GET, `TransferExportController`).
  - **Import** - `ImportDiffService` dry-run preview (create/update/skip/error counts, capped row lists); `ImportCommitService` transactional commit (type-precedence rules, vendor guard). `TransferSchema`, `RowMapper`, `ParsedRow`, `ImportDiff` DTOs.
  - `FormatRegistry` + 8 format implementations: `CsvWriter`/`CsvReader`, `JsonWriter`/`JsonReader`, `XlsxWriter`/`XlsxReader`, `OdsWriter`/`OdsReader`.
  - `SpreadsheetSupport::available()` gates XLSX/ODS; `SpreadsheetUnavailableException` thrown when requested without openspout.
  
- **Shared navigation menu** - `x-lingua::nav` anonymous Blade component on all 5 admin pages (Languages, Translations, Statistics, Transfer, Settings). Active-page highlighting (`variant="filled"` + `aria-current="page"`). `lingua.nav.enabled` config key (default `true`) + toggle in Settings UI (Routing & Navigation card).
  
- **Bundled translation dataset** - 26 locales × 7 groups (auth, pagination, passwords, validation, http-statuses, errors, notifications); 5902 strings; aligned to Laravel v13.14.0. Read by `BundledTranslationSource`. Replaces laravel-lang as the provisioning source. Merged into the database during `syncToDatabase()` and `installLocale()`. Bundled notification translations projected into `lang/{locale}.json` via `NotificationProjector` (non-destructive merge, `.lingua-managed.json` sidecar for selective removal on uninstall).
  
- **`LocaleRegistry`** - internal static dataset (129+ locales) replacing `laravel-lang/common` facade. `LocaleInfo` final readonly VO (`code`, `regional`, `type`, `name`, `native`, `direction`).
  
- **Statistics page** (`/lingua/statistics`, `lingua.statistics`) - per-language coverage with progress bars, group breakdown, missing-key drill-down, vendor toggle.
  
- **Settings page** (`/lingua/settings`, `lingua.settings`) - persistent UI settings in `lingua_settings` table; `LinguaSetting` model; `SelectorMode` enum (sidebar/modal/dropdown/headless).
  
- **Headless language selector** (`lingua::headless-language-selector`) - zero-CSS, `data-lingua-*` attributes, named `$item`/`$current` slots.
  
- **`ManagesLocale` trait** - shared locale management (`changeLocale()`, `languages()` computed, `currentUrl` capture) for all selector components (sidebar, dropdown, modal, headless).
  
- **`AtomicFileWriter`** - stateless I/O helper: writes via temp-file + atomic `rename()`; verifies all return values; calls `opcache_invalidate($path, true)` after PHP file writes; `ensureDir()`, `put()`, `putJson()`, `putPhp()` methods.
  
- **`MigrationPublisher`** - driver-aware selective migration publish. File driver skips `create_language_lines_table`. Idempotent (skips already-published basenames). Used by `lingua:install` and `lingua:storage`.
  
- **`CacheKey` helper** - canonical cache key builder: `{prefix}.{locale}.{group}` and `{prefix}.{locale}.{vendor}::{group}`.
  
- **`VendorTranslationProtectedException`** - thrown when attempting to delete a vendor-owned translation. Vendor translations can be edited (value/type) but not deleted.
  
- **`x-lingua::select`** - custom searchable/clearable select with native Popover API (`popover="manual"` + `showPopover()`/`hidePopover()`). Eliminates Flux modal `transform`/`overflow` stacking context issues. Fallback: `position:fixed` for browsers without Popover API support.
  
- **`HtmlSanitizer`** - DOM-based whitelist sanitizer with per-tag allowed-attribute map and URI scheme validation.
  
- **Pro extension hooks** - `suppress_pro_nudge` config, `pro_upgrade_url`, `extensions.enabled` kill-switch; `ExtensionRegistry` for third-party Livewire component injection via `allTranslationTabComponents()` / `allTranslationActionComponents()`.
  
- **Facade additions** - `get(?string $locale)`, `getDefault()`, `getFallback()`, `available()`, `installed()`, `notInstalled()`, `isInstalled(?string $locale)`, `isAvailable(?string $locale)`, `info(mixed $locale)`, `installDefaultLanguage()`, `updateLanguages()`, `setVendorTranslation()`. `optimize()` deprecated (surgical cache invalidation makes it unnecessary).
  
- **9-locale UI translations** (ar, en, es, fr, hi, it, pt, ru, zh) for nav menu, Transfer page, Settings routing/nav toggles, and security error messages.
  
- **`lingua:install` improvements** - arrow-key `select()` driver prompt (CI-friendly numbered fallback); file-mode deploy warnings (Forge/Envoyer/CI overwrite, dirty tree); driver-aware migration publish.
  
- **`lingua:sync-to-local --force`** - override file-mode no-op guard for deliberate DB→file export.
  
- **`TranslationFactory`** - `->core()` and `->vendor(string $vendor)` states; `HasFactory` added to `Translation` model.
  
- **`LangFileKeyParityTest`** - guards key parity across all 9 bundled UI locale files.
  

#### Changed

- **`LinguaManager` extends `FileLoader`** (not Spatie's `TranslationLoaderManager`); registered via `extend()` on the `translation.loader` binding; DB translations take precedence over file translations at runtime.
- **Vendor translation loading driver-aware** - `LinguaManager::load()` namespace branch checks driver; database mode resolves vendor groups from DB (cached via `getVendorTranslationsForGroup()`); file mode falls back to `parent::load()`.
- **Surgical cache invalidation** - `Cache::forget()` per `(locale, group)` pair on `Translation::saved`/`deleted`. Bulk sync flushes only affected keys. Global `Artisan::call('cache:clear')` removed from all sync paths.
- **`lingua:add` / `lingua:remove` / `lingua:update-lang`** - fully DB-native; no `laravel-lang` file download or Artisan injection via concatenated strings.
- **`Translation::syncToDatabase()`** - two-pass: default locale processed first as key reference; bundled translations merged before app lang files; vendor keys imported only for installed locales; per-row cache suppressed during bulk sync (single bust at end).
- **Language statistics** - PHP aggregation via `Translation::translationCounts()` (multi-DB safe, no JSON-SQL functions).
- **Migrations** - split into three files: `create_language_lines_table`, `create_languages_table`, `create_lingua_settings_table`. `language_lines.text` changed to `nullable()` (no SQL DEFAULT expression). `languages.regional` nullable.
- **Admin UI layout** - Languages toolbar: 2-row flex (Row 1: search + new-language; Row 2: 3 sync/update buttons, DB-mode only). Translations toolbar: `grid grid-cols-12` → `flex flex-wrap`. Section gap standardized to `gap-6` across all 5 admin pages. Modal selector inline style → Tailwind `w-32`.
- **`Language::scopeActive()`** renamed to `scopeOrdered()` for semantic accuracy; `scopeActive()` preserved as a delegate.
- **Asset serving** - CSS/JS served directly from package via `lingua.assets` route (no publish to `public/` required or supported). `lingua-assets` publish tag removed.
- **`lingua:install` migration handling** - driver-scoped `MigrationPublisher::publishFor()` instead of blanket publish-all; file mode never publishes `create_language_lines_table`.
- **`config/lingua.php`** - reorganized into domain groups; new keys: `gate`, `storage.driver`, `nav.enabled`, `links.translations`, `ui.sticky_top`, `cache.store`/`cache.prefix`, `suppress_pro_nudge`, `pro_upgrade_url`, `extensions.enabled`.
- **`Language\Table`** - portable LIKE escaping via `ESCAPE '!'` (MySQL / PostgreSQL / SQLite / SQL Server safe).
- **`LinguaMiddleware`** - DB lookup wrapped in `try/catch`; session write conditional on locale change (avoids marking session dirty on every request).
- **Graceful degrade** - `Lingua` facade read methods, `ManagesLocale::languages()`, `LinguaSetting::get()` catch `QueryException` when Lingua tables are absent (post-uninstall or pre-migration); return safe defaults.
- **`LinguaSeeder` conditional** - database driver only; file-mode install uses `Lingua::installDefaultLanguage()`.

#### Fixed

- Locale switch always redirected to home page - `ManagesLocale::initLocaleState()` now captures `request()->getRequestUri()`; `changeLocale()` guard uses local-path regex immune to `APP_URL` host mismatch.
- Bilingual CSV import skipped all rows when target locale code mismatched column header (e.g. `it_IT` vs `it - Italian`) - `RowMapper::findLocaleValue()` single-candidate fallback.
- File-mode stale translation row after edit - `AtomicFileWriter::putPhp()` calls `opcache_invalidate($path, true)` after atomic rename.
- Language stat accessors (`getTotalStringsAttribute`, `getTranslatedStringsAttribute`) crashed in file-mode with `SQLSTATE[42S02]` - routed through `app(TranslationRepository::class)->counts()`.
- `Import` public `$errors` property shadowed Livewire's `ViewErrorBag` → `@error()` called `getBag()` on an array (fatal) - renamed to `$rowErrors`.
- `transfer` block missing from 8 non-English bundled locales - nav menu and Transfer page showed raw translation keys.
- `DatabaseRepository::installLocale()` not seeding bundled translations for new locales - replaced skip-if-absent guard with `Translation::updateOrCreate()`.
- `Translations::mount()` `TypeError` under `strict_types` - `#[Url]`-bound `bool $showOnlyMissing` received string `"1"` from redundant `request('m', false)` re-read.
- `Translation::countByLocale()` - replaced PostgreSQL-only `whereRaw('(text->>?) IS NOT NULL')` with PHP aggregation.
- `Language::setDefault()` - two UPDATE queries now wrapped in `DB::transaction()`.
- `NotificationProjector::writeJson` - atomic I/O via `AtomicFileWriter`; manifest updated only after successful write.
- Translations resurrect after language delete - `RemoveLangCommand` and `Language\Delete` no longer call `syncToDatabase()` post-deletion.
- `Language\Table` search - `Language::exists()` replaces `.get()->isEmpty()` for bootstrap guard.
- `syncToLocal()` - all `file_put_contents`/`mkdir` calls via `AtomicFileWriter`.
- `syncToDatabase()` - targeted cache invalidation per affected `(locale, group)` pair.
- `Modals::closeModal()` - early return when `$modalName` is empty.
- `WireDirective::getAttributes()` undefined method fatal in `autocomplete.blade.php`.
- `TranslationFactory` - previously called non-existent `getGroupKey()` and swapped `group`/`key` variables.
- Various null-safe operator missing in `Lingua`, `LinguaServiceProvider`, `LinguaMiddleware`, `Translation\Delete::mount()`.

#### Removed

- `spatie/laravel-translation-loader` dependency.
- `laravel-lang/common` and all `LaravelLang\*` service providers.
- `lingua-assets` publish tag (assets served directly via `lingua.assets` route).
- Dead code: `$queryString` manual tracking in `Translations.php`, `getGroupKey()` on `Translation` model, `$canDelete`, `$canSetDefault`, `$syncDatabase`, `$totalStrings` unused properties, redundant facade vendor guard.


---

## 1.1.7 - 2026-05-11

### Security

- **[HIGH] Artisan argument injection in Commands** - `AddLangCommand`, `RemoveLangCommand`, `UpdateLangCommand` concatenated locale strings directly into `Artisan::call()` bypassing the `validateLocale()` guard used in `Lingua.php`. Converted all calls to array form `['locales' => [$locale]]` with correct argument name.
- **[HIGH] Artisan injection in `Translation::syncToDatabase()`** - Same concatenation pattern used when bootstrapping the default locale. Fixed to array form.
- **[HIGH] Artisan injection in `LinguaSeeder`** - Same pattern in seeder bootstrap. Fixed to array form.
- **[MEDIUM] Open redirect via Livewire `$currentUrl`** - `ManagesLocale::changeLocale()` redirected to `$currentUrl`, a public Livewire property modifiable via network snapshot. Added same-origin validation: host must match `config('app.url')`, otherwise falls back to `/`.
- **[MEDIUM] Stored XSS in HTML translation preview** - `translation/row.blade.php` rendered `$defaultValue` via `{!! !!}` for all translation types. Raw output now scoped to `html` type only; `text` type uses escaped `{{ }}`.
- **[MEDIUM] Unescaped translation strings in delete modal** - `translation/delete.blade.php` rendered `$deleteHeader` and `$deleteAction` via `{!! !!}`. Changed to `{{ }}`.
- **[MEDIUM] Unvalidated drag-drop parameters in `Language\Sort`** - `updateLanguageOrder()` accepted untyped `$item` and `$position` from Livewire network payload. Added `int` type hints and early return for negative positions.
- **[MEDIUM] Unbounded `$perPage` in Translations** - URL-bound `?p=` parameter had no upper limit, enabling DoS via large result sets. Clamped to `max(1, min(x, 100))` in `mount()`.

### Fixed

- `helpers.php` `linguaLanguageCode()` returned `Stringable` instead of `string` under strict types. Added `->toString()` cast.
- Removed dead `$queryString` manual tracking in `Translations.php`; `updatedCurrentLocale()` now builds redirect params inline from `#[Url]`-tracked properties.
- `LinguaServiceProvider` loader/translator singletons now survive composer script context (null config fallback, DB try/catch).

### Changed

- Added `declare(strict_types=1)` to all remaining PHP source files.
- Removed dead code: `$value` in `Translation\Create`, `getGroupKey()` in `Translation` model, `$syncDatabase`/`$totalStrings` in `Language\Table`, `$canDelete` in `Translation\Delete`, `$canSetDefault` in `Language\SetDefault`, unused `View|Factory` imports in `Language\Delete`, dead `$attribute` in `Translation\Row::validationAttributes()`.


---

## 1.1.6 - 2026-05-08

### Security

- **[CRITICAL] Path traversal in asset route** - `routes/web.php` `lingua/assets/{path}` used `where('path', '.*')` allowing `../` traversal outside `src/dist/`. Fixed with `realpath()` jail: resolved path must start with the `src/dist` base or request is rejected with 404.
- **[CRITICAL] Default middleware now includes `auth`** - Lingua management routes were publicly accessible by default. Default changed to `['web', 'auth']`. Existing published configs unaffected; new installations now require authentication.
- **[CRITICAL] SQL injection via locale in JSON column path** - `Translations.php` interpolated user-controlled `$currentLocale` directly into raw JSON column paths. Added regex validation; invalid formats fall back to default locale.
- **[HIGH] Path traversal in `syncToLocal()`** - User-controlled `group` values used directly in `file_put_contents()` paths without sanitization. Added `assertSafePathSegment()` guard on locale, group, and vendor before all filesystem writes.
- **[HIGH] Artisan argument injection** - `addLanguage()`, `removeLanguage()`, `updateLanguages()` concatenated locale strings into `Artisan::call()` strings, allowing flag injection. Added `validateLocale()` regex guard before all Artisan calls.
- **[HIGH] TypeError on null Language model** - `Translations.php` declared `public Language $language` (non-nullable) but `::first()` can return null. Changed to `public ?Language $language = null` with null-safe fallback in `mount()`.
- **[MEDIUM] Unvalidated session locale in middleware** - `LinguaMiddleware` applied session locale to `app()->setLocale()` without format validation. Added ISO locale regex check; malformed values fall back to default.

### Fixed

- **[MEDIUM] Wrong unique column in Translation Update** - `rules()` validated `key` against the `group_key` composite column, making uniqueness inoperative. Fixed to scope by `group` + `is_vendor`.
- **[MEDIUM] Double `requiredIf` logic bug** - Two independent `requiredIf` conditions caused cross-field required errors (e.g. `textValue` required when locale=default even if type=html). Fixed to single combined condition.
- **[MEDIUM] Broken markdown type detection** - `writeTranslation()` used `Str::markdown() === $original` which never matched. Replaced with heuristic regex for common markdown markers.
- **[MEDIUM] Missing enum validation on `translationType`** - Both Create and Update accepted any string; invalid types silently produced empty translations. Changed to `Rule::enum(LinguaType::class)`.

### Changed

- Added `declare(strict_types=1)` to all source files missing it.
- Fixed bare `use DB;` in `Models/Language.php` → `use Illuminate\Support\Facades\DB`.


---

## 1.1.5 - 2026-04-29

### Changed

- **Modal backdrop blur** - Added `backdrop-filter: blur(4px)` to all Flux modal backdrops via `[data-flux-modal] > dialog::backdrop` in `resources/css/lingua/styles.css`. PostCSS prefix-selector scopes the rule to `.lingua [data-flux-modal] > dialog::backdrop`. Both unprefixed and `-webkit-` variants emitted by Lightning CSS for full browser coverage (Chrome, Firefox, Safari).


---

## 1.1.4 - 2026-04-22

### Fixed

- **Modal centering** - Tailwind preflight scoped under `.lingua *` set `margin: 0` on all descendants, overriding the browser UA `margin: auto` that centers native `<dialog>:modal`. Added explicit `[data-flux-modal] > dialog { margin: auto }` rule in `resources/css/lingua/styles.css` (post-prefixed by postcss-prefix-selector to `.lingua [data-flux-modal] > dialog`). Affects all modal Livewire components: `Language/Create`, `Language/Delete`, `Language/SetDefault`, `Translation/Create`, `Translation/Delete`, `Translation/Update`, and `LanguageSelector` in modal mode.


---

## 1.1.3 - 2026-04-16

### Changed

- Normalized indentation across all Blade views and PHP source files for consistency.
- Removed unused static assets from the package.


---

## 1.1.2 - 2026-04-14

### Fixed

- **`Lingua::languages()` serialization** - Method now correctly returns a plain Eloquent `Collection`, fixing a serialization error when the result was stored in a Livewire component property.

### Changed

- **CSS isolation** - All Tailwind utilities, preflight resets, and CSS custom properties are now scoped under `.lingua` via `postcss-prefix-selector`. This prevents Lingua's bundled Tailwind styles from leaking into the host application's global stylesheet. The `lingua.min.css` dist file is rebuilt accordingly.


---

## 1.1.0 - 2026-04-10

### Added

- **Statistics page** (`/lingua/statistics`, route `lingua.statistics`) - per-language coverage with progress bars,
  breakdown by translation group, missing-key counts with direct links to the translation editor.
  Vendor translations can be included or excluded via a toggle.
  
- **Settings page** (`/lingua/settings`, route `lingua.settings`) - persistent UI settings stored in the
  `lingua_settings` table. Selector mode and flag display are now configurable from the UI without editing config files
  or redeploying.
  
- **`LinguaSetting` model** - key/value store for package settings with typed get/set API and automatic fallback to
  `config/lingua.php`. Known keys: `selector.show_flags` (bool) and `selector.mode` (string).
  
- **`SelectorMode` enum** - backed string enum with four cases: `sidebar`, `modal`, `dropdown`, `headless`.
  Each case provides a `label()` and `description()` method; `selectValues()` returns all cases as value/label pairs.
  
- **Headless language selector** (`lingua::headless-language-selector`) - zero-CSS Livewire component rendering
  semantic HTML with `data-lingua-*` attributes (`data-lingua-selector`, `data-lingua-list`, `data-lingua-item`,
  `data-lingua-active`, `data-lingua-button`, `data-lingua-name`, `data-lingua-native`, `data-lingua-code`) and named
  slots (`$item`, `$current`) for full styling freedom.
  
- **`ManagesLocale` trait** - extracts shared locale management logic (`languages()`, `changeLocale()`,
  `currentLocale`) used by both `LanguageSelector` and `HeadlessLanguageSelector`.
  
- **Translation files** for `ar`, `es`, `fr`, `hi`, `it`, `pt`, `ru`, `zh` - complete translations of all Lingua UI
  strings including the new statistics and settings sections.
  

### Changed

- **`Translation::syncToDatabase()`** - refactored to a two-pass approach: default locale processed first and used as
  the reference key set; non-default locale keys are skipped if absent from the default locale; vendor keys are
  imported only when the locale is installed in the `languages` table. A `$syncing` flag suppresses per-row
  `cache:clear` calls during bulk sync, firing once at the end instead. No existing DB records are ever deleted.
  
- **Migration structure** - `create_lingua_table` split into three separate files: `create_language_lines_table`,
  `create_languages_table`, `create_lingua_settings_table`. Granular rollback is now possible per table.
  
- **`Language::scopeActive()`** - renamed to `scopeOrdered()` for semantic accuracy; `scopeActive()` preserved as a
  delegate with `@todo` for future `is_active` field filtering.
  
- **Asset serving** - compiled assets are now served directly from the package via the `lingua.assets` route.
  Publishing assets is no longer required or supported; the `lingua-assets` publish tag has been removed.
  
- **Language selector config** - `selector.mode` now accepts `headless` as a valid value in addition to the existing
  `sidebar`, `modal`, and `dropdown` options.
  

### Fixed

- **`languages` migration** - removed erroneous standalone `->unique()` on the `regional` column; the composite
  unique index `unique_language_type` on `[code, regional]` is the correct constraint.
  
- **Statistics `includeVendor` toggle** - replaced conflicting `wire:model.live` + `wire:change` directives with
  `:checked` binding + `wire:change`, eliminating the double-toggle that caused the switch to have no effect.
  


---

## Upgrading to 1.1.0

Run migrations to create the new `lingua_settings` table:

    php artisan migrate
    
If you previously published assets, they are no longer needed. The package now serves its own compiled assets
automatically. You can safely delete `public/vendor/lingua/` from your project.

If you have customised `config/lingua.php`, your values continue to work as fallback - no changes required.


---

## 1.0.3 - 2026-04-09

### 2026-04-09

#### Fixed

- **`Translation/Create` - group preserved after creation** - the `group` field is now retained after a successful save, allowing multiple keys to be added to the same group consecutively without reselecting it. Only `key`, `translationType`, and value fields are reset.
- **`Translation/Create` and `Translation/Update` - whitespace normalization** - `group` and `key` values are sanitized with `Str::squish()->trim()` before being persisted, preventing keys with leading, trailing, or excess internal spaces from being stored.

#### Tests

- `CreateTest`: corrected `group` assertions to reflect preservation after creation; added whitespace normalization test for `group` and `key`.
- `UpdateTest`: added whitespace normalization test for `group` and `key`; added test confirming that vendor translation `group` and `key` fields are immutable.

## Rivalex Lingua - 2026-04-09

### 2026-04-09

#### Fixed

- **`Translation/Create` - group preserved after creation** - the `group` field is now retained after a successful save, allowing multiple keys to be added to the same group consecutively without reselecting it. Only `key`, `translationType`, and value fields are reset.
- **`Translation/Create` and `Translation/Update` - whitespace normalization** - `group` and `key` values are sanitized with `Str::squish()->trim()` before being persisted, preventing keys with leading, trailing, or excess internal spaces from being stored.

#### Tests

- `CreateTest`: corrected `group` assertions to reflect preservation after creation; added whitespace normalization test for `group` and `key`.
- `UpdateTest`: added whitespace normalization test for `group` and `key`; added test confirming that vendor translation `group` and `key` fields are immutable.


---

## Rivalex Lingua - 2026-04-01

### 2026-04-01

#### Fixed

- **`Lingua::updateLanguages()` / `lingua:update-lang`** - `lang:update` was called without arguments, causing laravel-lang to refresh translation files for every locale present in the vendor filesystem, including locales not installed in the `languages` table. Both the facade method and the Artisan command now resolve the installed locales from the database and pass them explicitly to `lang:update {locales}`. If no languages are installed the update is skipped entirely.


---

## Rivalex Lingua - 2026-03-28

### 2026-03-28

#### Added

- **Laravel 13 compatibility** - `illuminate/contracts ^13.0` and `orchestra/testbench ^11.0` confirmed; no breaking-change impact from the framework. Livewire 4.x is fully compatible with Laravel 13.
- README and documentation updated to reflect supported range: Laravel **11 | 12 | 13**.


---

## Rivalex Lingua - 2026-03-27

All notable changes to `lingua` will be documented in this file.

### 2026-03-26

#### Fixed

- **`Lingua::isDefaultLocale()`** - missing null-safe operator caused a `TypeError` when called with a locale code that has no matching record in the database; now returns `false` safely.
- **`LinguaServiceProvider::registerTranslator()`** - `Language::default()->code` replaced with `Language::default()?->code` to avoid `TypeError` during bootstrap when the `languages` table is empty or not yet migrated.
- **`LinguaMiddleware`** - same nullsafe fix: `Language::default()->code` → `Language::default()?->code`.
- **`Translation\Delete::mount()`** - accessing `->name` on the result of `Language::first()` without a null guard caused a `TypeError` when the locale was absent from the database; now falls back to the locale code string.
- **`LanguageSelector::changeLocale()`** - the method accepted any arbitrary string passed as `$locale` and stored it directly in the session without validating it against the installed languages, allowing an attacker to inject arbitrary locale codes. It now silently returns early if the locale is not found in the database.
- **`Language::setDefault()`** - the two separate UPDATE queries ran outside a transaction, leaving a window where no language was marked as default. Both queries are now wrapped in `DB::transaction()`.
- **`Language\Create`** - misleading log message "Languages reorder failed" corrected to "Add language failed".

#### Added

- **`Lingua::addLanguage(string $locale)`** - facade method (and docblock) for installing language files via `lang:add`.
- **`Lingua::removeLanguage(string $locale)`** - new facade method for removing language files via `lang:rm --force`; mirrors the file-management step of `lingua:remove`.
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
  
- **`VendorTranslationProtectedException`** - thrown when attempting to delete a vendor-owned translation.
  
- **Vendor translation protection** - vendor translations cannot be deleted from the UI; attempting to do so dispatches a `vendor_translation_protected` event and closes the modal instead.
  
- **Vendor translation locking in `Update`** - when editing a vendor translation, `group` and `key` fields are locked; only the text value and type may be changed.
  
- `isVendor` property exposed on the `Translation/Update` Livewire component for view-layer awareness.
  
- Feature tests: `LinguaFacadeTest` and `VendorTranslationTest` covering the full facade API and vendor-protection behaviour.
  
- Helper unit tests extended to cover new utility cases.
  

#### Changed

- `Translation/Update`: vendor translations skip the group/key update path and only persist `type` and text changes.
- `Translation/Delete`: vendor translations are intercepted before deletion and trigger a protected event instead.
- `LinguaServiceProvider`: updated to register the vendor protection exception and related bindings.

## 2026-03-26

### Fixed

- **`Lingua::isDefaultLocale()`** - missing null-safe operator caused a `TypeError` when called with a locale code that has no matching record in the database; now returns `false` safely.
- **`LinguaServiceProvider::registerTranslator()`** - `Language::default()->code` replaced with `Language::default()?->code` to avoid `TypeError` during bootstrap when the `languages` table is empty or not yet migrated.
- **`LinguaMiddleware`** - same nullsafe fix: `Language::default()->code` → `Language::default()?->code`.
- **`Translation\Delete::mount()`** - accessing `->name` on the result of `Language::first()` without a null guard caused a `TypeError` when the locale was absent from the database; now falls back to the locale code string.
- **`LanguageSelector::changeLocale()`** - the method accepted any arbitrary string passed as `$locale` and stored it directly in the session without validating it against the installed languages, allowing an attacker to inject arbitrary locale codes. It now silently returns early if the locale is not found in the database.
- **`Language::setDefault()`** - the two separate UPDATE queries ran outside a transaction, leaving a window where no language was marked as default. Both queries are now wrapped in `DB::transaction()`.
- **`Language\Create`** - misleading log message "Languages reorder failed" corrected to "Add language failed".

### Added

- **`Lingua::addLanguage(string $locale)`** - facade method (and docblock) for installing language files via `lang:add`.
- **`Lingua::removeLanguage(string $locale)`** - new facade method for removing language files via `lang:rm --force`; mirrors the file-management step of `lingua:remove`.
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
  
- **`VendorTranslationProtectedException`** - thrown when attempting to delete a vendor-owned translation.
  
- **Vendor translation protection** - vendor translations cannot be deleted from the UI; attempting to do so dispatches a `vendor_translation_protected` event and closes the modal instead.
  
- **Vendor translation locking in `Update`** - when editing a vendor translation, `group` and `key` fields are locked; only the text value and type may be changed.
  
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

- **`AddLangCommand`** - artisan command to add a new language to the application.
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

## 2026-03-11 - Initial Release

### Added

- **Language management UI** - Livewire-powered CRUD for application languages (create, delete, set default, reorder).
  
- **Translation management UI** - Livewire-powered interface for browsing and editing translations per locale, including rich-text (TipTap) and plain-text editor modes.
  
- **Language selector** - embeddable Livewire component in three styles: `dropdown`, `modal`, and `sidebar`.
  
- **Artisan commands**:
  
  - `lingua:sync-to-database` - import local translation files into the database.
  - `lingua:sync-to-local` - export database translations back to local files.
  - `lingua:update-lang` - update language files via `laravel-lang`.
  
- **`LinguaMiddleware`** - sets the active locale from the authenticated user's language preference.
  
- **`LinguaSeeder`** - seeds the database with language records.
  
- **Database migration** - creates the `lingua_languages` and `lingua_translations` tables.
  
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
  
