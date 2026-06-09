<?php

use Rivalex\Lingua\Database\Db;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\LinguaManager;

/**
 * This file contains the configuration options for the lingua package.
 * You can customize the settings to fit your application's needs.
 */

return [

    /* =========================================================================
     * LOCALE DEFAULTS
     * ========================================================================= */

    /*
     * Specifies the location of the lang folder within the Laravel project.
     * The lang_path() function typically returns the default location of the lang folder: ./lang
     * You can override this value by specifying a different path to the lang folder, e.g., ./resources/lang
     */
    'lang_dir' => lang_path(),

    /*
     * Specifies the default locale for the application.
     * If not specified, it will default to the app.locale configuration value, or 'en' if not set.
     */
    'default_locale' => config('app.locale', 'en'),

    /*
     * Specifies the fallback locale for the application.
     * If not specified, it will default to the app.fallback_locale configuration value, or 'en' if not set.
     */
    'fallback_locale' => config('app.fallback_locale', 'en'),

    /*
     * Specifies the session variable used to store the selected locale.
     * By default, the session variable is 'locale'.
     * You can change this value to customize the session variable name.
     */
    'session_variable' => 'locale',

    /* =========================================================================
     * ROUTING
     * ========================================================================= */

    /*
     * Specifies the middleware that should be applied to the lingua routes.
     *
     * ⚠️  SECURITY: The translation management UI must be protected by an
     * authentication gate. The 'auth' middleware is included by default.
     * Remove it only if you gate access elsewhere (e.g. a custom policy).
     * You can add role-based guards (e.g. 'role:admin') as needed.
     */
    'middleware' => ['web', 'auth'],

    /*
     * Specifies the prefix for the lingua routes.
     * By default, the routes will be prefixed with 'lingua'.
     * You can change this value to customize the route prefix for lingua.
     */
    'routes_prefix' => 'lingua',

    /*
     * Optional URI fragment appended to every Lingua page route.
     * Useful for multi-tenant or parameterized routing where the host app
     * needs an extra segment handled by its own middleware or route bindings.
     * Example: '{team?}' → /lingua/languages/{team?}, /lingua/translations/{locale?}/{team?}
     * Lingua does not read these parameters; optional params are forwarded automatically
     * by Laravel when calling route('lingua.*') from within a bound request.
     *
     * For prefix-based params use routes_prefix instead:
     * 'routes_prefix' => '{team}/lingua'  → /{team}/lingua/languages, etc.
     */
    'routes_extra_parameters' => null,

    /* =========================================================================
     * UI / PRESENTATION
     * ========================================================================= */

    /*
     * Specifies the mode for the language selector and the display of language flags.
     * By default, the mode is 'sidebar'.
     * You can change this value to 'modal' or 'dropdown' to customize the language selector mode.
     * The 'show_flags' option determines whether to display language flags.
     * You can set this value to true or false to enable or disable language flags respectively.
     */
    'selector' => [
        'mode' => 'sidebar',
        'show_flags' => true,
    ],

    /*
     * Editor configuration options for the language editor.
     * You can customize the available editor features and their behavior.
     */
    'editor' => [
        'headings' => false,
        'bold' => true,
        'italic' => true,
        'underline' => true,
        'strikethrough' => false,
        'subscript' => true,
        'superscript' => true,
        'blockquote' => false,
        'code-line' => false,
        'code-block' => false,
        'bullet' => true,
        'ordered' => true,
        'clear' => true,
        'code-mode' => false,
    ],

    /*
     * Layout used when Lingua page components render full-page (via lingua routes).
     * null = use the Livewire default (livewire.layout config, e.g. components.layouts.app).
     * Set to your project layout when it differs, e.g. 'layouts.application'.
     * Ignored when components are embedded inline (<livewire:lingua::languages />).
     */
    'layout' => null,

    /*
     * Use wire:navigate on internal redirects (locale switch, locale tab change).
     * Default is false — opt in only when the host app has Livewire SPA navigation enabled.
     * Set to true when your app uses <livewire:navigate> or Livewire's navigate feature.
     */
    'navigate' => false,

    /*
     * Configurable navigation links from Lingua UI to package pages.
     *
     * translations.enabled — false renders external links as plain text/labels.
     *   Applies to the language table row (language name) and the statistics
     *   missing-keys panel. Does NOT suppress the in-page locale-selector redirect
     *   inside the Translations component — that always navigates to `route`.
     *
     * translations.route — route name for all links to the translation page.
     *   Receives ['locale' => $code] (+ 'q', 'g' from locale-selector redirect).
     *   Custom routes can ignore unknown query parameters.
     */
    'links' => [
        'translations' => [
            'enabled' => true,
            'route' => 'lingua.translations',
        ],
    ],

    /*
     * UI presentation tweaks.
     *
     * sticky_top — CSS top offset for the sticky filter bar in the Translations page.
     * Use this when the host app renders a fixed header above the Lingua pages so the
     * bar does not hide behind it. Accepts an integer (converted to rem, e.g. 4 → "4rem")
     * or a CSS string (e.g. 'var(--app-header-height)', '64px').
     * Default 0 means the bar sticks to the very top of the viewport.
     */
    'ui' => [
        'sticky_top' => 0,
    ],

    /* =========================================================================
     * STORAGE / LOADERS
     * ========================================================================= */

    /*
     * Storage driver for translation lines.
     * 'database' — reads/writes via the language_lines table (default, full-featured).
     * 'file'     — reads/writes lang/ PHP and JSON files directly; DB is not used for runtime lookups.
     *              Sync commands (lingua:sync-to-database) are still available to migrate.
     * Override via LINGUA_STORAGE_DRIVER env variable.
     */
    'storage' => [
        'driver' => env('LINGUA_STORAGE_DRIVER', 'database'),
    ],

    /*
     * Language lines will be fetched by these loaders. You can put any class here that implements
     * the Rivalex\Lingua\Contracts\TranslationLoader interface.
     */
    'translation_loaders' => [
        Db::class,
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Illuminate\Database\Eloquent\Model and exposes a static
     * getTranslationsForGroup(string $locale, string $group): array method.
     */
    'model' => Translation::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => LinguaManager::class,

    /*
     * Base translations path for the bundled translation dataset.
     * Phase 1: empty placeholder. Phase 2: populated with base locale files.
     * Override to point to an external satellite package directory.
     */
    'base_translations_path' => __DIR__.'/../resources/translations',

    /*
     * Path to the bundled notification translation files.
     * These are projected into lang/{locale}.json at locale install-time by NotificationProjector.
     * They are NOT read by BundledTranslationSource and do NOT flow into the DB.
     */
    'base_notifications_path' => __DIR__.'/../resources/notifications',

    /*
     * Cache configuration for database-backed translations.
     * Translations are cached forever per (locale, group) pair and invalidated
     * selectively on model save/delete. Use 'store' to override the default
     * cache driver (e.g. 'redis'). Leave null to use the app default.
     */
    'cache' => [
        'store' => env('LINGUA_CACHE_STORE', null),
        'prefix' => env('LINGUA_CACHE_PREFIX', 'lingua.trans'),
    ],

    /* =========================================================================
     * PRO & EXTENSIONS
     * ========================================================================= */

    /*
     * Lingua Pro upgrade nudge settings.
     * Set suppress_pro_nudge to true to hide all Pro CTAs (e.g. for users who have lingua-pro installed).
     * pro_upgrade_url is the link used in upgrade prompts.
     */
    'suppress_pro_nudge' => env('LINGUA_SUPPRESS_PRO_NUDGE', false),
    'pro_upgrade_url' => env('LINGUA_PRO_UPGRADE_URL', 'https://lingua.rivalex.dev'),

    /*
     * Extension hook system settings.
     * Set 'enabled' to false to disable all lingua extension hooks globally
     * (emergency kill switch — useful during incidents without uninstalling extensions).
     */
    'extensions' => [
        'enabled' => env('LINGUA_EXTENSIONS_ENABLED', true),
    ],
];
