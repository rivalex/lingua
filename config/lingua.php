<?php

use Rivalex\Lingua\Database\Db;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\LinguaManager;

/**
 * This file contains the configuration options for the lingua package.
 * You can customize the settings to fit your application's needs.
 */

return [

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
     * Specifies the middleware that should be applied to the lingua routes.
     * By default, the 'web' middleware is applied.
     * You can add additional middleware as needed for your application such as 'auth', 'guest', etc.
     */
    'middleware' => ['web'],

    /*
     * Specifies the prefix for the lingua routes.
     * By default, the routes will be prefixed with 'lingua'.
     * You can change this value to customize the route prefix for lingua.
     */
    'routes_prefix' => 'lingua',

    /*
     * Specifies the session variable used to store the selected locale.
     * By default, the session variable is 'locale'.
     * You can change this value to customize the session variable name.
     */
    'session_variable' => 'locale',

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
     * Language lines will be fetched by these loaders. You can put any class here that implements
     * the Spatie\TranslationLoader\TranslationLoaders\TranslationLoader-interface.
     */
    'translation_loaders' => [
        Db::class,
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Spatie\TranslationLoader\LanguageLine.
     */
    'model' => Translation::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => LinguaManager::class,
];
